<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once '../../conexion/init.php';
?>
<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Actividades Académicas</title>
    <link rel="stylesheet" href="../../styles/header.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
        integrity="sha512-iecdLmaskl7CVkqkXNQ/ZH/XLlvWZOJyj7Yy7tcenmpD1ypASozpmT/E0iPtmFIB46ZmdtAc9eNBvH0H/ZpiBw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" href="../../assets/img/icon2.png">
    <style>
        :root {
            --primary-color: #0E4A86;
            --primary-light: #1565C0;
            --success-color: #4caf50;
            --warning-color: #ff9800;
            --danger-color: #f44336;
            --text-primary: #212121;
            --text-secondary: #757575;
            --border-color: #e0e0e0;
        }

        body {
            background-color: #fafafa;
            font-family: 'Roboto', sans-serif;
        }

        .main-container {
            margin-left: 100px;
            padding: 20px;
            transition: margin-left 0.4s;
            min-height: 100vh;
        }

        body:not(.sidebar-collapsed) .main-container {
            margin-left: 300px;
            transition: margin-left 0.4s;
        }

        body.sidebar-collapsed .main-container {
            margin-left: 100px;
            transition: margin-left 0.4s;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(14, 74, 134, 0.2);
        }

        .page-title {
            font-size: 24px;
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .page-subtitle {
            font-size: 14px;
            margin-top: 6px;
            opacity: 0.9;
        }

        .user-info {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            padding: 8px 12px;
            margin-top: 12px;
            font-size: 13px;
        }

        .stat-card-compact {
            background: white;
            padding: 12px;
            border-radius: 8px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            text-align: center;
            border-top: 3px solid var(--primary-color);
        }

        .stat-number-compact {
            font-size: 24px;
            font-weight: 700;
            display: block;
        }

        .stat-label-compact {
            color: var(--text-secondary);
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
        }

        .filters-container-compact {
            background: white;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 20px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
        }

        .activity-card {
            background: white;
            border-radius: 8px;
            border-left: 4px solid var(--primary-color);
            padding: 16px;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.2s ease;
            margin-bottom: 12px;
            height: 100%;
        }

        .activity-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .activity-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0 0 8px 0;
        }

        .activity-subject {
            background: var(--primary-color);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .activity-description {
            color: var(--text-secondary);
            font-size: 14px;
            line-height: 1.4;
            margin-bottom: 12px;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .activity-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
            font-size: 13px;
        }

        .status-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pendiente {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffcc80;
        }

        .status-entregada {
            background: #e8f5e8;
            color: #2e7d32;
            border: 1px solid #a5d6a7;
        }

        .status-vencida {
            background: #ffebee;
            color: #c62828;
            border: 1px solid #ef9a9a;
        }

        .search-container {
            position: relative;
        }

        .search-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-secondary);
        }

        .search-input {
            padding-left: 40px;
        }

        .loading {
            text-align: center;
            padding: 60px;
            color: var(--text-secondary);
        }

        .error-container {
            background: #ffebee;
            border: 1px solid #f44336;
            border-radius: 8px;
            padding: 20px;
            margin: 20px 0;
            color: #c62828;
        }

        @keyframes spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        .spin {
            animation: spin 1s linear infinite;
        }

        @media (max-width: 768px) {
            .main-container {
                margin-left: 0;
                padding: 16px;
            }

            .sidebar-expanded .main-container {
                margin-left: 0;
            }

            .page-title {
                font-size: 20px;
            }
        }
    </style>
</head>

<body>
    <?php include '../../includes/design/header.php'; ?>
    <?php include '../../includes/design/sidebar.php'; ?>

    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-mortarboard"></i>
                <span id="pageTitle">Actividades Académicas</span>
            </h1>
            <p class="page-subtitle">Gestiona y realiza seguimiento a todas tus actividades asignadas</p>
            <div class="user-info" id="userInfo">
                <i class="bi bi-person"></i> <span id="userName">Cargando información del usuario...</span>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="stat-card-compact">
                    <span class="stat-number-compact text-warning" id="stat-pendientes">0</span>
                    <span class="stat-label-compact">Pendientes</span>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="stat-card-compact">
                    <span class="stat-number-compact text-success" id="stat-entregadas">0</span>
                    <span class="stat-label-compact">Entregadas</span>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="stat-card-compact">
                    <span class="stat-number-compact text-danger" id="stat-vencidas">0</span>
                    <span class="stat-label-compact">Vencidas</span>
                </div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="stat-card-compact">
                    <span class="stat-number-compact text-primary" id="stat-total">0</span>
                    <span class="stat-label-compact">Total</span>
                </div>
            </div>
        </div>

        <div class="filters-container-compact">
            <div class="row g-2">
                <div class="col-md-4">
                    <div class="search-container">
                        <i class="bi bi-search search-icon"></i>
                        <input type="text" class="form-control form-control-sm search-input" id="searchInput"
                            placeholder="Buscar actividades...">
                    </div>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filterEstado">
                        <option value="">Todos los estados</option>
                        <option value="pendiente">Pendientes</option>
                        <option value="entregada">Entregadas</option>
                        <option value="vencida">Vencidas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filterTrimestre">
                        <option value="">Todos los trimestres</option>
                        <option value="actual">Trimestre actual</option>
                        <option value="anterior">Trimestres anteriores</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="filterMateria">
                        <option value="">Todas las materias</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select class="form-select form-select-sm" id="itemsPerPage">
                        <option value="6">6 por página</option>
                        <option value="12" selected>12 por página</option>
                        <option value="24">24 por página</option>
                        <option value="all">Todas</option>
                    </select>
                </div>
            </div>
        </div>

        <div id="activitiesContainer">
            <div class="loading">
                <i class="bi bi-arrow-clockwise spin"></i>
                <h4>Cargando actividades...</h4>
                <p>Obteniendo información de la base de datos...</p>
            </div>
        </div>

        <nav aria-label="Paginación de actividades" id="paginationContainer" style="display: none;">
            <ul class="pagination pagination-sm justify-content-center" id="pagination"></ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        class ActivityManager {
            constructor() {
                this.activities = [];
                this.filteredActivities = [];
                this.userInfo = null;
                this.filters = {
                    search: '',
                    estado: '',
                    trimestre: '',
                    materia: ''
                };
                this.currentPage = 1;
                this.itemsPerPage = 12;
                this.totalPages = 1;
                this.init();
            }

            async init() {
                try {
                    await this.loadActivities();
                    await this.loadMaterias();
                    this.setupEventListeners();
                    this.setupPagination();
                    this.applyFilters();
                } catch (error) {
                    console.error('Error en inicialización:', error);
                    this.showError(error.message);
                }
            }

            async loadActivities() {
                try {
                    const response = await fetch('get_activities.php', {
                        method: 'GET',
                        headers: {
                            'Content-Type': 'application/json',
                            'Cache-Control': 'no-cache'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`Error HTTP: ${response.status} - ${response.statusText}`);
                    }

                    const responseText = await response.text();
                    let data;

                    try {
                        data = JSON.parse(responseText);
                    } catch (parseError) {
                        throw new Error('Error al procesar respuesta del servidor: ' + parseError.message);
                    }

                    if (data.success) {
                        this.activities = data.activities || [];

                        if (data.debug && data.debug.user_info) {
                            this.userInfo = data.debug.user_info;
                            this.updateUserInfo();
                        }
                    } else {
                        throw new Error(data.message || 'Error desconocido al cargar actividades');
                    }

                } catch (error) {
                    throw error;
                }
            }

            updateUserInfo() {
                if (this.userInfo) {
                    const userName = document.getElementById('userName');
                    const pageTitle = document.getElementById('pageTitle');

                    const fullName = `${this.userInfo.nombres || ''} ${this.userInfo.apellidos || ''}`.trim();
                    const role = this.userInfo.rol || 'Usuario';

                    if (userName) {
                        userName.innerHTML = `${fullName} - ${role} | Doc: ${this.userInfo.documento || this.userInfo.id || 'N/A'}`;
                    }

                    if (pageTitle) {
                        pageTitle.textContent = 'Actividades Académicas';
                    }
                }
            }

            async loadMaterias() {
                try {
                    const response = await fetch('get_materias.php');

                    if (!response.ok) {
                        return;
                    }

                    const data = await response.json();

                    if (data.success && data.materias) {
                        const select = document.getElementById('filterMateria');
                        if (select) {
                            while (select.children.length > 1) {
                                select.removeChild(select.lastChild);
                            }

                            data.materias.forEach(materia => {
                                const option = document.createElement('option');
                                option.value = materia.id_materia;
                                option.textContent = materia.materia;
                                select.appendChild(option);
                            });
                        }
                    }
                } catch (error) {
                    console.error('Error cargando materias:', error);
                }
            }

            setupEventListeners() {
                const elements = {
                    searchInput: document.getElementById('searchInput'),
                    filterEstado: document.getElementById('filterEstado'),
                    filterTrimestre: document.getElementById('filterTrimestre'),
                    filterMateria: document.getElementById('filterMateria')
                };

                if (elements.searchInput) {
                    elements.searchInput.addEventListener('input', (e) => {
                        this.filters.search = e.target.value.toLowerCase();
                        this.applyFilters();
                    });
                }

                if (elements.filterEstado) {
                    elements.filterEstado.addEventListener('change', (e) => {
                        this.filters.estado = e.target.value;
                        this.applyFilters();
                    });
                }

                if (elements.filterTrimestre) {
                    elements.filterTrimestre.addEventListener('change', (e) => {
                        this.filters.trimestre = e.target.value;
                        this.applyFilters();
                    });
                }

                if (elements.filterMateria) {
                    elements.filterMateria.addEventListener('change', (e) => {
                        this.filters.materia = e.target.value;
                        this.applyFilters();
                    });
                }
            }

            setupPagination() {
                const itemsPerPageSelect = document.getElementById('itemsPerPage');
                if (itemsPerPageSelect) {
                    itemsPerPageSelect.addEventListener('change', (e) => {
                        this.itemsPerPage = e.target.value === 'all' ? this.filteredActivities.length : parseInt(e.target.value);
                        this.currentPage = 1;
                        this.renderActivities();
                    });
                }
            }

            applyFilters() {
                this.filteredActivities = this.activities.filter(activity => {
                    if (this.filters.search) {
                        const searchTerm = this.filters.search.toLowerCase();
                        const titleMatch = activity.titulo && activity.titulo.toLowerCase().includes(searchTerm);
                        const descMatch = activity.descripcion && activity.descripcion.toLowerCase().includes(searchTerm);
                        if (!titleMatch && !descMatch) {
                            return false;
                        }
                    }

                    if (this.filters.estado && activity.estado !== this.filters.estado) {
                        return false;
                    }

                    if (this.filters.trimestre) {
                        const currentMonth = new Date().getMonth() + 1;
                        const isCurrentTrimester = this.isCurrentTrimester(
                            activity.trimestre_inicio,
                            activity.trimestre_fin,
                            currentMonth
                        );

                        if (this.filters.trimestre === 'actual' && !isCurrentTrimester) {
                            return false;
                        }
                        if (this.filters.trimestre === 'anterior' && isCurrentTrimester) {
                            return false;
                        }
                    }

                    if (this.filters.materia && activity.id_materia != this.filters.materia) {
                        return false;
                    }

                    return true;
                });

                this.renderActivities();
                this.updateStats();
            }

            isCurrentTrimester(inicio, fin, currentMonth) {
                if (!inicio || !fin) return false;
                return currentMonth >= inicio && currentMonth <= fin;
            }

            renderActivities() {
                const container = document.getElementById('activitiesContainer');
                if (!container) return;

                if (this.filteredActivities.length === 0) {
                    container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="bi bi-clipboard-x" style="font-size: 48px; color: var(--text-secondary); opacity: 0.5;"></i>
                        <h5 class="mt-3 text-muted">No se encontraron actividades</h5>
                        <p class="text-muted">No hay actividades que coincidan con los filtros aplicados.</p>
                    </div>
                `;
                    document.getElementById('paginationContainer').style.display = 'none';
                    return;
                }

                this.totalPages = this.itemsPerPage === this.filteredActivities.length ? 1 : Math.ceil(this.filteredActivities.length / this.itemsPerPage);
                const startIndex = (this.currentPage - 1) * this.itemsPerPage;
                const endIndex = this.itemsPerPage === this.filteredActivities.length ? this.filteredActivities.length : startIndex + this.itemsPerPage;
                const currentActivities = this.filteredActivities.slice(startIndex, endIndex);

                const activitiesHTML = currentActivities.map(activity => `
                <div class="col-md-6 col-lg-4 mb-3">
                    <div class="activity-card h-100">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="activity-title">${activity.titulo || 'Sin título'}</h6>
                            <span class="activity-subject">${activity.materia || 'Sin materia'}</span>
                        </div>
                        
                        <p class="activity-description">${activity.descripcion || 'Sin descripción disponible'}</p>
                        
                        <div class="activity-meta">
                            <small class="text-muted">
                                <i class="bi bi-calendar-event"></i>
                                ${this.formatDate(activity.fecha_entrega)}
                            </small>
                            
                            <span class="status-badge status-${activity.estado}">
                                ${this.getStatusIcon(activity.estado)} ${this.getStatusText(activity.estado)}
                            </span>
                            
                            ${activity.nota ? `<small class="badge" style="background-color: var(--primary-color); color: white;"><i class="bi bi-star-fill"></i> ${activity.nota}</small>` : ''}
                        </div>
                    </div>
                </div>
            `).join('');

                container.innerHTML = `<div class="row">${activitiesHTML}</div>`;

                if (this.totalPages > 1) {
                    this.renderPagination();
                    document.getElementById('paginationContainer').style.display = 'block';
                } else {
                    document.getElementById('paginationContainer').style.display = 'none';
                }
            }

            renderPagination() {
                const pagination = document.getElementById('pagination');
                if (!pagination) return;

                let paginationHTML = '';

                paginationHTML += `
                <li class="page-item ${this.currentPage === 1 ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage - 1}">
                        <i class="bi bi-chevron-left"></i>
                    </a>
                </li>
            `;

                for (let i = 1; i <= this.totalPages; i++) {
                    if (i === 1 || i === this.totalPages || (i >= this.currentPage - 1 && i <= this.currentPage + 1)) {
                        paginationHTML += `
                        <li class="page-item ${i === this.currentPage ? 'active' : ''}">
                            <a class="page-link" href="#" data-page="${i}">${i}</a>
                        </li>
                    `;
                    } else if (i === this.currentPage - 2 || i === this.currentPage + 2) {
                        paginationHTML += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
                    }
                }

                paginationHTML += `
                <li class="page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}">
                    <a class="page-link" href="#" data-page="${this.currentPage + 1}">
                        <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            `;

                pagination.innerHTML = paginationHTML;

                pagination.querySelectorAll('.page-link').forEach(link => {
                    link.addEventListener('click', (e) => {
                        e.preventDefault();
                        const page = parseInt(e.target.closest('.page-link').dataset.page);
                        if (page && page !== this.currentPage && page >= 1 && page <= this.totalPages) {
                            this.currentPage = page;
                            this.renderActivities();
                            window.scrollTo({
                                top: 0,
                                behavior: 'smooth'
                            });
                        }
                    });
                });
            }

            updateStats() {
                const stats = {
                    pendientes: this.filteredActivities.filter(a => a.estado === 'pendiente').length,
                    entregadas: this.filteredActivities.filter(a => a.estado === 'entregada').length,
                    vencidas: this.filteredActivities.filter(a => a.estado === 'vencida').length,
                    total: this.filteredActivities.length
                };

                const elements = {
                    pendientes: document.getElementById('stat-pendientes'),
                    entregadas: document.getElementById('stat-entregadas'),
                    vencidas: document.getElementById('stat-vencidas'),
                    total: document.getElementById('stat-total')
                };

                Object.keys(elements).forEach(key => {
                    if (elements[key]) {
                        elements[key].textContent = stats[key];
                    }
                });
            }

            formatDate(dateString) {
                if (!dateString) return 'Sin fecha';

                try {
                    const date = new Date(dateString);
                    const options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric',
                        weekday: 'long'
                    };
                    return date.toLocaleDateString('es-ES', options);
                } catch (error) {
                    return dateString;
                }
            }

            getStatusText(status) {
                const statusMap = {
                    'pendiente': 'Pendiente',
                    'entregada': 'Entregada',
                    'vencida': 'Vencida'
                };
                return statusMap[status] || status || 'Sin estado';
            }

            getStatusIcon(status) {
                const iconMap = {
                    'pendiente': '<i class="bi bi-clock"></i>',
                    'entregada': '<i class="bi bi-check-circle"></i>',
                    'vencida': '<i class="bi bi-x-circle"></i>'
                };
                return iconMap[status] || '<i class="bi bi-list-task"></i>';
            }

            showError(message) {
                const container = document.getElementById('activitiesContainer');
                if (container) {
                    container.innerHTML = `
                    <div class="error-container">
                        <h4><i class="bi bi-exclamation-triangle"></i> Error</h4>
                        <p><strong>Mensaje:</strong> ${message}</p>
                        <div class="mt-3">
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Reintentar
                            </button>
                            <button class="btn btn-secondary" onclick="window.location.href='../../'">
                                <i class="bi bi-house"></i> Ir al Inicio
                            </button>
                        </div>
                    </div>
                `;
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            window.activityManager = new ActivityManager();
        });

        if (document.readyState !== 'loading') {
            window.activityManager = new ActivityManager();
        }
    </script>
</body>

</html>