// Support page animations and interactions
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
  const formContainer = document.querySelector(".support-form-container");
  if (formContainer) {
    observer.observe(formContainer);
  }

  // Observe info cards
  const infoCards = document.querySelectorAll(".support-info-card");
  infoCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Enhanced form validation with real-time feedback
  const form = document.querySelector(".support-form");
  const submitBtn = document.querySelector(".support-submit-btn");

  if (form && submitBtn) {
    const inputs = form.querySelectorAll("input, textarea");

    // Real-time validation
    inputs.forEach((input) => {
      input.addEventListener("blur", validateField);
      input.addEventListener("input", clearErrors);
    });

    // Character counter for textarea
    const textarea = document.getElementById("problema");
    if (textarea) {
      const counter = document.createElement("div");
      counter.className = "character-counter";
      counter.style.cssText = `
        font-size: 0.8rem;
        color: var(--text-light);
        text-align: right;
        margin-top: 0.5rem;
      `;
      textarea.parentNode.appendChild(counter);

      textarea.addEventListener("input", () => {
        const length = textarea.value.length;
        counter.textContent = `${length} caracteres`;

        if (length < 10) {
          counter.style.color = "#dc3545";
        } else if (length < 50) {
          counter.style.color = "#f59e0b";
        } else {
          counter.style.color = "#10b981";
        }
      });
    }

    // Enhanced form submission
    form.addEventListener("submit", (e) => {
      if (!validateForm()) {
        e.preventDefault();
        return false;
      }

      // Add loading state
      submitBtn.innerHTML =
        '<i class="bx bx-loader-alt bx-spin me-2"></i>Enviando solicitud...';
      submitBtn.disabled = true;
    });
  }

  // Counter animation for stats
  const animateCounter = (element, target, suffix = "") => {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }

      if (suffix === "h") {
        element.textContent = `<${Math.floor(current)}${suffix}`;
      } else if (suffix === "%") {
        element.textContent = `${Math.floor(current)}${suffix}`;
      } else {
        element.textContent = target.toString();
      }
    }, 30);
  };

  // Observe stats for counter animation
  const statNumbers = document.querySelectorAll(".support-stat-number");
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const text = entry.target.textContent;
        if (text.includes("%")) {
          animateCounter(entry.target, 98, "%");
        } else if (text.includes("h")) {
          entry.target.textContent = "<2h";
        } else {
          entry.target.textContent = "24/7";
        }
        statsObserver.unobserve(entry.target);
      }
    });
  });

  statNumbers.forEach((stat) => {
    statsObserver.observe(stat);
  });

  // Smooth accordion behavior
  const accordionButtons = document.querySelectorAll(
    ".support-accordion-button"
  );
  accordionButtons.forEach((button) => {
    button.addEventListener("click", () => {
      // Add smooth transition effect
      setTimeout(() => {
        button.scrollIntoView({
          behavior: "smooth",
          block: "nearest",
        });
      }, 300);
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

  // Validate based on field type and name
  if (field.name === "nombreSoporte") {
    if (value.length < 2) {
      showFieldError(field, "El nombre debe tener al menos 2 caracteres");
      return false;
    }
  }

  if (field.type === "email") {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (value && !emailPattern.test(value)) {
      showFieldError(field, "Por favor ingresa un correo válido");
      return false;
    }
  }

  if (field.name === "numeroSoporte") {
    const phonePattern = /^[+]?[0-9\s\-$$$$]{10,}$/;
    if (value && !phonePattern.test(value)) {
      showFieldError(field, "Ingresa un número de teléfono válido");
      return false;
    }
  }

  if (field.name === "problema") {
    if (value.length < 10) {
      showFieldError(
        field,
        "Describe el problema con más detalle (mínimo 10 caracteres)"
      );
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
  const nombre = document.getElementById("nombreSoporte");
  const correo = document.getElementById("correoSoporte");
  const numero = document.getElementById("numeroSoporte");
  const problema = document.getElementById("problema");

  let isValid = true;

  // Validate all fields
  if (!validateField({ target: nombre })) isValid = false;
  if (!validateField({ target: correo })) isValid = false;
  if (!validateField({ target: numero })) isValid = false;
  if (!validateField({ target: problema })) isValid = false;

  return isValid;
}

// Add CSS for enhanced form validation and animations
const style = document.createElement("style");
style.textContent = `
    .support-info-card.animate-in {
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
    
    body:not(.loaded) .support-hero-content > * {
        opacity: 0;
    }
    
    body.loaded .support-hero-content > * {
        animation-play-state: running;
    }
    
    .bx-spin {
        animation: spin 1s linear infinite;
    }
    
    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
    
    .character-counter {
        transition: color 0.3s ease;
    }
`;
document.head.appendChild(style);
