// About page animations and interactions
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

  // Observe mission cards
  const missionCards = document.querySelectorAll(".about-mission-card");
  missionCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Observe commitment cards
  const commitmentCards = document.querySelectorAll(".about-commitment-card");
  commitmentCards.forEach((card, index) => {
    card.style.opacity = "0";
    card.style.transform = "translateY(30px)";
    card.style.transition = `all 0.6s ease ${index * 0.2}s`;
    observer.observe(card);
  });

  // Counter animation for stats
  const animateCounter = (element, target) => {
    let current = 0;
    const increment = target / 50;
    const timer = setInterval(() => {
      current += increment;
      if (current >= target) {
        current = target;
        clearInterval(timer);
      }

      const text = element.textContent;
      if (text.includes("+")) {
        element.textContent = Math.floor(current) + "+";
      } else if (text.includes("%")) {
        element.textContent = Math.floor(current) + "%";
      } else {
        element.textContent = Math.floor(current);
      }
    }, 30);
  };

  // Observe stats for counter animation
  const statNumbers = document.querySelectorAll(".about-stat-number");
  const statsObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
      if (entry.isIntersecting) {
        const target = entry.target.textContent;
        const numericValue = Number.parseInt(target.replace(/\D/g, ""));
        animateCounter(entry.target, numericValue);
        statsObserver.unobserve(entry.target);
      }
    });
  });

  statNumbers.forEach((stat) => {
    statsObserver.observe(stat);
  });

  // Parallax effect for floating elements
  window.addEventListener("scroll", () => {
    const scrolled = window.pageYOffset;
    const floatingElements = document.querySelectorAll(
      ".about-floating-element"
    );

    floatingElements.forEach((element, index) => {
      const speed = 0.3 + index * 0.1;
      const yPos = -(scrolled * speed);
      element.style.transform = `translateY(${yPos}px)`;
    });
  });

  // Add loading animation
  window.addEventListener("load", () => {
    document.body.classList.add("loaded");
  });
});

// Add CSS for scroll animations
const style = document.createElement("style");
style.textContent = `
    .about-mission-card.animate-in,
    .about-commitment-card.animate-in {
        opacity: 1 !important;
        transform: translateY(0) !important;
    }
    
    body:not(.loaded) .about-hero-content > * {
        opacity: 0;
    }
    
    body.loaded .about-hero-content > * {
        animation-play-state: running;
    }
`;
document.head.appendChild(style);
