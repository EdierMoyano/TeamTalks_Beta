document.addEventListener("DOMContentLoaded", () => {
            const observerOptions = {
                threshold: 0.1,
                rootMargin: "0px 0px -50px 0px",
            }

            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add("animate-in")
                    }
                })
            }, observerOptions)

            const featureCards = document.querySelectorAll(".feature-card")
            featureCards.forEach((card) => {
                observer.observe(card)
            })

            const buttons = document.querySelectorAll(".btn")
            buttons.forEach((button) => {
                button.addEventListener("mouseenter", function() {
                    this.style.transform = "translateY(-2px)"
                })

                button.addEventListener("mouseleave", function() {
                    this.style.transform = "translateY(0)"
                })
            })

            window.addEventListener("scroll", () => {
                const scrolled = window.pageYOffset
                const parallaxElements = document.querySelectorAll(".floating-element")

                parallaxElements.forEach((element, index) => {
                    const speed = 0.5 + index * 0.1
                    const yPos = -(scrolled * speed)
                    element.style.transform = `translateY(${yPos}px)`
                })
            })

            window.addEventListener("load", () => {
                document.body.classList.add("loaded")
            })
        })

        const style = document.createElement("style")
        style.textContent = `
      .feature-card {
          opacity: 0;
          transform: translateY(30px);
          transition: all 0.6s ease;
      }

      .feature-card.animate-in {
          opacity: 1;
          transform: translateY(0);
      }

      .feature-card:nth-child(2) {
          transition-delay: 0.1s;
      }

      .feature-card:nth-child(3) {
          transition-delay: 0.2s;
      }

      body:not(.loaded) .hero-content > * {
          opacity: 0;
      }

      body.loaded .hero-content > * {
          animation-play-state: running;
      }
    `
        document.head.appendChild(style)