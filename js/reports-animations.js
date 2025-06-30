// Reports page animations and interactions
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
  const formContainer = document.querySelector(".reports-form-container");
  if (formContainer) {
    observer.observe(formContainer);
  }

  // Observe info cards
  const infoCards = document.querySelectorAll(".reports-info-card");
  infoCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Enhanced form validation with real-time feedback
  const form = document.querySelector(".reports-form");
  const submitBtn = document.querySelector(".reports-submit-btn");

  if (form && submitBtn) {
    const inputs = form.querySelectorAll("input, select, textarea");

    // Real-time validation
    inputs.forEach((input) => {
      input.addEventListener("blur", validateField);
      input.addEventListener("input", clearErrors);
    });

    // Character counter for textarea
    const textarea = document.getElementById("descripcionReporte");
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

        if (length < 20) {
          counter.style.color = "#dc3545";
          counter.textContent += " (mínimo 20)";
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
        '<i class="bx bx-loader-alt bx-spin me-2"></i>Enviando reporte...';
      submitBtn.disabled = true;
    });
  }

  // Dynamic form behavior based on report type
  const reportTypeSelect = document.getElementById("tipoReporte");
  if (reportTypeSelect) {
    reportTypeSelect.addEventListener("change", (e) => {
      const selectedType = e.target.value;
      const textarea = document.getElementById("descripcionReporte");

      // Update placeholder based on report type
      if (selectedType.includes("acceso") || selectedType.includes("cuenta")) {
        textarea.placeholder =
          "Describe el problema de acceso:\n\n• ¿Qué mensaje de error ves?\n• ¿Cuándo comenzó el problema?\n• ¿Has intentado restablecer tu contraseña?\n• ¿Desde qué dispositivo intentas acceder?";
      } else if (
        selectedType.includes("sistema") ||
        selectedType.includes("caída")
      ) {
        textarea.placeholder =
          "Describe el error del sistema:\n\n• ¿Qué estabas haciendo cuando ocurrió?\n• ¿Qué mensaje de error aparece?\n• ¿El problema persiste?\n• ¿Afecta a toda la aplicación o solo una parte?";
      } else if (selectedType.includes("formularios")) {
        textarea.placeholder =
          "Describe el problema con formularios:\n\n• ¿Qué formulario no funciona?\n• ¿Qué sucede cuando intentas enviarlo?\n• ¿Aparece algún mensaje de error?\n• ¿Has intentado desde otro navegador?";
      } else {
        textarea.placeholder =
          "Describe detalladamente el problema:\n\n• ¿Qué estaba haciendo cuando ocurrió?\n• ¿Qué esperaba que pasara?\n• ¿Qué pasó en su lugar?\n• ¿Cuándo comenzó el problema?\n• ¿Has intentado alguna solución?";
      }
    });
  }

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
  if (field.type === "email") {
    const emailPattern = /^[a-zA-Z0-9._-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,6}$/;
    if (value && !emailPattern.test(value)) {
      showFieldError(field, "Por favor ingresa un correo válido");
      return false;
    }
  }

  if (field.name === "tipoReporte") {
    if (!value) {
      showFieldError(field, "Por favor selecciona el tipo de problema");
      return false;
    }
  }

  if (field.name === "descripcionReporte") {
    if (value.length < 20) {
      showFieldError(
        field,
        "Describe el problema con más detalle (mínimo 20 caracteres)"
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
  const correo = document.getElementById("correoSoporte");
  const tipoReporte = document.getElementById("tipoReporte");
  const descripcion = document.getElementById("descripcionReporte");

  let isValid = true;

  // Validate all required fields
  if (!validateField({ target: correo })) isValid = false;
  if (!validateField({ target: tipoReporte })) isValid = false;
  if (!validateField({ target: descripcion })) isValid = false;

  return isValid;
}

// Add CSS for enhanced form validation and animations
const style = document.createElement("style");
style.textContent = `
    .reports-info-card.animate-in {
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
    
    body:not(.loaded) .reports-hero-content > * {
        opacity: 0;
    }
    
    body.loaded .reports-hero-content > * {
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
    
    /* Smooth transitions for form elements */
    .reports-input,
    .reports-select,
    .reports-textarea {
        transition: all 0.3s ease;
    }
    
    .reports-input:hover,
    .reports-select:hover,
    .reports-textarea:hover {
        border-color: #dc2626;
    }
`;
document.head.appendChild(style);
