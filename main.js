/**
 * NSBM NewsDesk - Campus News Management System
 * Frontend Interactions & Form Validation
 * Module: Web and Mobile Application Development (SE102.3)
 */

document.addEventListener('DOMContentLoaded', function () {
  // 0. Dark / Light Mode Theme Controller (Default: Dark)
  const currentTheme = localStorage.getItem('nsbm_theme') || 'dark';
  document.documentElement.setAttribute('data-theme', currentTheme);

  const themeToggleBtns = document.querySelectorAll('#themeToggleBtn, .theme-toggle-btn');
  function updateThemeIcons(theme) {
    const sunSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>';
    const moonSvg = '<svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>';

    themeToggleBtns.forEach(btn => {
      const iconSpan = btn.querySelector('.theme-icon');
      if (iconSpan) {
        iconSpan.innerHTML = theme === 'dark' ? sunSvg : moonSvg;
      }
      btn.setAttribute('title', theme === 'dark' ? 'Switch to Light Theme' : 'Switch to Dark Theme');
    });
  }
  updateThemeIcons(currentTheme);

  themeToggleBtns.forEach(btn => {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const activeTheme = document.documentElement.getAttribute('data-theme') || 'dark';
      const newTheme = activeTheme === 'dark' ? 'light' : 'dark';
      document.documentElement.setAttribute('data-theme', newTheme);
      localStorage.setItem('nsbm_theme', newTheme);
      updateThemeIcons(newTheme);
    });
  });

  // 1. Mobile Navigation Toggle & Backdrop Controller
  const mobileToggle = document.getElementById('mobileNavToggle');
  const navMenu = document.getElementById('navbarMenu');
  if (mobileToggle && navMenu) {
    mobileToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      const isOpen = navMenu.classList.toggle('open');
      mobileToggle.classList.toggle('active', isOpen);
      mobileToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    // Close menu when tapping anywhere outside
    document.addEventListener('click', function (e) {
      if (navMenu.classList.contains('open') && !navMenu.contains(e.target) && !mobileToggle.contains(e.target)) {
        navMenu.classList.remove('open');
        mobileToggle.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
      }
    });

    // Close menu when clicking any nav link
    navMenu.querySelectorAll('.nav-link').forEach(link => {
      link.addEventListener('click', function () {
        navMenu.classList.remove('open');
        mobileToggle.classList.remove('active');
        mobileToggle.setAttribute('aria-expanded', 'false');
      });
    });
  }

  // 2. Flash Alert Dismissal
  const flashAlerts = document.querySelectorAll('.flash-alert');
  flashAlerts.forEach(function (alert) {
    const closeBtn = alert.querySelector('.flash-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', function () {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 250);
      });
    }
    // Auto dismiss after 6 seconds
    setTimeout(() => {
      if (document.body.contains(alert)) {
        alert.style.opacity = '0';
        setTimeout(() => alert.remove(), 250);
      }
    }, 6000);
  });

  // 3. Image Upload Live Preview
  const imageInput = document.getElementById('articleImageInput');
  const imagePreview = document.getElementById('imagePreviewContainer');
  if (imageInput && imagePreview) {
    imageInput.addEventListener('change', function () {
      const file = this.files[0];
      if (file) {
        // Validate size (max 5MB)
        if (file.size > 5 * 1024 * 1024) {
          alert('File is too large! Maximum allowed size is 5MB.');
          this.value = '';
          imagePreview.innerHTML = '';
          return;
        }
        // Validate type
        const validTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
        if (!validTypes.includes(file.type)) {
          alert('Invalid file format! Please upload JPG, PNG, WEBP, or GIF.');
          this.value = '';
          imagePreview.innerHTML = '';
          return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
          imagePreview.innerHTML = `
            <div style="position:relative; margin-top:10px; max-width:300px; border-radius:8px; overflow:hidden; border:1px solid var(--border);">
              <img src="${e.target.result}" alt="Preview" style="width:100%; height:160px; object-fit:cover;">
              <span style="position:absolute; bottom:6px; right:6px; background:rgba(0,0,0,0.7); color:#fff; font-size:11px; padding:2px 6px; border-radius:4px;">Selected</span>
            </div>
          `;
        };
        reader.readAsDataURL(file);
      } else {
        imagePreview.innerHTML = '';
      }
    });
  }

  // 4. Bookmark Toggle via AJAX
  const bookmarkButtons = document.querySelectorAll('.bookmark-toggle-btn');
  bookmarkButtons.forEach(function (btn) {
    btn.addEventListener('click', function (e) {
      e.preventDefault();
      const articleId = this.getAttribute('data-article-id');
      if (!articleId) return;

      const icon = this.querySelector('svg') || this;

      fetch('api/toggle_bookmark.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded'
        },
        body: 'article_id=' + encodeURIComponent(articleId)
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 'unauthorized') {
          window.location.href = 'login.php';
          return;
        }
        if (data.status === 'success') {
          if (data.bookmarked) {
            this.classList.add('active');
            this.setAttribute('title', 'Remove bookmark');
            if (this.querySelector('.bookmark-text')) {
              this.querySelector('.bookmark-text').textContent = 'Saved in Bookmarks';
            }
          } else {
            this.classList.remove('active');
            this.setAttribute('title', 'Save article');
            if (this.querySelector('.bookmark-text')) {
              this.querySelector('.bookmark-text').textContent = 'Save Article';
            }
            // If on the bookmarks page, optionally fade out card
            const bookmarkCard = document.getElementById('bookmark-card-' + articleId);
            if (bookmarkCard) {
              bookmarkCard.style.opacity = '0';
              setTimeout(() => bookmarkCard.remove(), 300);
            }
          }
        }
      })
      .catch(err => {
        console.error('Bookmark error:', err);
      });
    });
  });

  // 5. Client-Side Form Validations
  // A. Registration Form Validation
  const registerForm = document.getElementById('registerForm');
  if (registerForm) {
    registerForm.addEventListener('submit', function (e) {
      const name = document.getElementById('fullName');
      const email = document.getElementById('email');
      const studentId = document.getElementById('studentId');
      const password = document.getElementById('password');
      const confirmPassword = document.getElementById('confirmPassword');
      let isValid = true;
      let errorMsg = '';

      if (name && name.value.trim().length < 3) {
        errorMsg = 'Please enter your full name (minimum 3 characters).';
        isValid = false;
      } else if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
        errorMsg = 'Please enter a valid email address.';
        isValid = false;
      } else if (password && password.value.length < 6) {
        errorMsg = 'Password must be at least 6 characters long.';
        isValid = false;
      } else if (password && confirmPassword && password.value !== confirmPassword.value) {
        errorMsg = 'Passwords do not match. Please verify.';
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
        showInlineError(registerForm, errorMsg);
      }
    });
  }

  // B. Article Submission & Create Form Validation
  const articleForm = document.getElementById('articleForm');
  if (articleForm) {
    articleForm.addEventListener('submit', function (e) {
      const title = document.getElementById('articleTitle');
      const category = document.getElementById('articleCategory');
      const content = document.getElementById('articleContent');
      let isValid = true;
      let errorMsg = '';

      if (title && title.value.trim().length < 5) {
        errorMsg = 'Article headline must be at least 5 characters long.';
        isValid = false;
      } else if (category && (!category.value || category.value === '')) {
        errorMsg = 'Please select a valid news category.';
        isValid = false;
      } else if (content && content.value.trim().length < 20) {
        errorMsg = 'Article content is too short. Please provide meaningful body text (min 20 characters).';
        isValid = false;
      }

      if (!isValid) {
        e.preventDefault();
        showInlineError(articleForm, errorMsg);
      }
    });
  }

  // C. Comment Submission Form Validation
  const commentForm = document.getElementById('commentForm');
  if (commentForm) {
    commentForm.addEventListener('submit', function (e) {
      const commentInput = document.getElementById('commentText');
      if (commentInput && commentInput.value.trim().length < 3) {
        e.preventDefault();
        showInlineError(commentForm, 'Comment must contain at least 3 characters.');
      }
    });
  }

  // Utility to show inline validation errors
  function showInlineError(formElement, message) {
    let existingError = formElement.querySelector('.client-error-box');
    if (!existingError) {
      existingError = document.createElement('div');
      existingError.className = 'flash-alert danger client-error-box';
      formElement.prepend(existingError);
    }
    existingError.textContent = '⚠ ' + message;
    existingError.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  // 6. Modal Open/Close Controls
  const modalTriggers = document.querySelectorAll('[data-modal-target]');
  modalTriggers.forEach(btn => {
    btn.addEventListener('click', function () {
      const targetId = this.getAttribute('data-modal-target');
      const modal = document.getElementById(targetId);
      if (modal) {
        modal.classList.add('open');
      }
    });
  });

  const modalCloses = document.querySelectorAll('[data-modal-close]');
  modalCloses.forEach(btn => {
    btn.addEventListener('click', function () {
      const modal = this.closest('.modal-backdrop');
      if (modal) {
        modal.classList.remove('open');
      }
    });
  });

  // Close modal when clicking backdrop
  document.querySelectorAll('.modal-backdrop').forEach(modal => {
    modal.addEventListener('click', function (e) {
      if (e.target === this) {
        this.classList.remove('open');
      }
    });
  });
});
