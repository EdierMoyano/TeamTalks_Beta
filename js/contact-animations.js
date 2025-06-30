// Contact page animations and interactions
document.addEventListener("DOMContentLoaded", () => {
  // Intersection Observer for scroll animations
  const observerOptions = {
    threshold: 0.1,
    rootMargin: "0px 0px -50px 0px",
  };

  const observer = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        entry.target.classList.add("animate-in");
      }
    });
  }, observerOptions);

  // Observe form container
  const formContainer = document.querySelector(".contact-form-container");
  if (formContainer) {
    observer.observe(formContainer);
  }

  // Observe help cards
  const helpCards = document.querySelectorAll(".contact-help-card");
  helpCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Observe info cards
  const infoCards = document.querySelectorAll(".contact-info-card");
  infoCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Form validation with better UX
  const form = document.querySelector(".contact-form");
  const submitBtn = document.querySelector(".contact-submit-btn");

  if (form && submitBtn) {
    const inputs = form.querySelectorAll("input, textarea");

    // Real-time validation
    inputs.forEach((input) => {
      input.addEventListener("blur", validateField);
      input.addEventListener("input", clearErrors);
    });

    // Enhanced form submission
    form.addEventListener("submit", (e) => {
      if (!validateForm()) {
        e.preventDefault();
        return false;
      }

      // Add loading state
      submitBtn.innerHTML =
        '<i class="bx bx-loader-alt bx-spin me-2"></i>Enviando...';
      submitBtn.disabled = true;
    });
  }

  // Smooth scroll for anchor links
  const anchorLinks = document.querySelectorAll('a[href^="#"]');
  anchorLinks.forEach((link) => {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute("href"));
      if (target) {
        target.scrollIntoView({
          behavior: "smooth",
          block: "start",
        });
      }
    });
  });

  // Add loading animation
  window.addEventListener("load", () => {
    document.body.classList.add("loaded");
  });
});

// Enhanced form validation
function validateField(e) {
  const field = e.target;
  const value = field.value.trim();

  // Remove existing error styling
  field.classList.remove("is-invalid");

  // Validate based on field type
  if (field.type === "email") {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (value && !emailPattern.test(value)) {
      showFieldError(field, "Por favor ingresa un correo válido");
      return false;
    }
  }

  if (field.required && !value) {
    showFieldError(field, "Este campo es obligatorio");
    return false;
  }

  return true;
}

function showFieldError(field, message) {
  field.classList.add("is-invalid");

  // Remove existing error message
  const existingError = field.parentNode.querySelector(".invalid-feedback");
  if (existingError) {
    existingError.remove();
  }

  // Add new error message
  const errorDiv = document.createElement("div");
  errorDiv.className = "invalid-feedback";
  errorDiv.textContent = message;
  field.parentNode.appendChild(errorDiv);
}

function clearErrors(e) {
  const field = e.target;
  field.classList.remove("is-invalid");
  const errorMsg = field.parentNode.querySelector(".invalid-feedback");
  if (errorMsg) {
    errorMsg.remove();
  }
}

function validateForm() {
  const correo = document.getElementById("correo");
  const mensaje = document.getElementById("mensaje");
  let isValid = true;

  // Validate email
  if (!validateField({ target: correo })) {
    isValid = false;
  }

  // Validate message
  if (!validateField({ target: mensaje })) {
    isValid = false;
  }

  return isValid;
}

// Add CSS for enhanced form validation
const style = document.createElement("style");
style.textContent = `
    .contact-help-card.animate-in,
    .contact-info-card.animate-in {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    .is-invalid {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.1) !important;
    }
    
    .invalid-feedback {
        display: block;
        width: 100%;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        color: #dc3545;
    }
    
    body:not(.loaded) .contact-hero-content > * {
        opacity: 0;
    }
    
    body.loaded .contact-hero-content > * {
        animation-play-state: running;
    }
    
    .bx-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
`;
document.head.appendChild(style);
