document.addEventListener('DOMContentLoaded', function() {
    // Referencias a elementos del DOM
    const sidebar = document.querySelector('.sidebar');
    const sidebarToggler = document.querySelector('.sidebar-toggler');
    const menuToggler = document.querySelector('.menu-toggler');
    const mobileToggle = document.querySelector('.mobile-toggle');
    
    // Verificar si hay una preferencia guardada para el estado del sidebar
    const sidebarState = localStorage.getItem('sidebarState');
    if (sidebarState === 'collapsed') {
        sidebar.classList.add('collapsed');
    }
    
    // Toggle sidebar en desktop
    if (sidebarToggler) {
        sidebarToggler.addEventListener('click', function() {
            sidebar.classList.toggle('collapsed');
            // Guardar preferencia
            if (sidebar.classList.contains('collapsed')) {
                localStorage.setItem('sidebarState', 'collapsed');
            } else {
                localStorage.setItem('sidebarState', 'expanded');
            }
        });
    }
    
    // Toggle sidebar en mobile
    if (menuToggler) {
        menuToggler.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }
    
    // Toggle sidebar desde el contenido principal en mobile
    if (mobileToggle) {
        mobileToggle.addEventListener('click', function() {
            sidebar.classList.toggle('mobile-open');
        });
    }
    
    // Cerrar sidebar en mobile al hacer clic fuera
    document.addEventListener('click', function(event) {
        const isClickInsideSidebar = sidebar.contains(event.target);
        const isClickOnMobileToggle = mobileToggle && mobileToggle.contains(event.target);
        const isClickOnMenuToggler = menuToggler && menuToggler.contains(event.target);
        
        if (!isClickInsideSidebar && !isClickOnMobileToggle && !isClickOnMenuToggler && window.innerWidth <= 768) {
            sidebar.classList.remove('mobile-open');
        }
    });
    
    // Manejo específico del dropdown de Aprendices
    const aprendicesDropdown = document.getElementById('aprendicesDropdown');
    const dropdownToggle = aprendicesDropdown ? aprendicesDropdown.querySelector('.dropdown-toggle') : null;
    
    if (dropdownToggle) {
        // Verificar si debe estar abierto por defecto (página activa)
        const hasActiveChild = aprendicesDropdown.querySelector('.dropdown-item.active') !== null;
        if (hasActiveChild) {
            aprendicesDropdown.classList.add('dropdown-active');
            const dropdownMenu = aprendicesDropdown.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                dropdownMenu.style.display = 'block';
            }
        }
        
        // Agregar evento de clic
        dropdownToggle.addEventListener('click', function(e) {
            e.preventDefault();
            aprendicesDropdown.classList.toggle('dropdown-active');
            
            // Rotar el icono
            const icon = this.querySelector('.dropdown-icon i');
            if (icon) {
                if (aprendicesDropdown.classList.contains('dropdown-active')) {
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-up');
                } else {
                    icon.classList.remove('bi-chevron-up');
                    icon.classList.add('bi-chevron-down');
                }
            }
            
            // Mostrar/ocultar el menú
            const dropdownMenu = aprendicesDropdown.querySelector('.dropdown-menu');
            if (dropdownMenu) {
                if (aprendicesDropdown.classList.contains('dropdown-active')) {
                    dropdownMenu.style.display = 'block';
                } else {
                    dropdownMenu.style.display = 'none';
                }
            }
        });
    }
    
    // Ajustar para dispositivos móviles
    function checkScreenSize() {
        if (window.innerWidth <= 768) {
            sidebar.classList.remove('collapsed');
        }
    }
    
    // Verificar tamaño de pantalla al cargar y al redimensionar
    checkScreenSize();
    window.addEventListener('resize', checkScreenSize);
});