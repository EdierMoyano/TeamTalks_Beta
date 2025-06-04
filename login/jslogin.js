
        function showpass() {
            const passw = document.getElementById("password");
            const iconshow = document.getElementById("showpass");
            if (passw.type === "password") {
                passw.type = "text";
                iconshow.classList.replace("bx-show", "bx-hide");
            } else {
                passw.type = "password";
                iconshow.classList.replace("bx-hide", "bx-show");
            }
        }

        // Validación para el campo numérico
        document.addEventListener('DOMContentLoaded', function() {
            const documentoInput = document.getElementById('documentId');
            
            documentoInput.addEventListener('keypress', function(e) {
                // Permitir solo números
                if (!/\d/.test(String.fromCharCode(e.keyCode))) {
                    e.preventDefault();
                }
            });
        });
