<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_name    = getCurrentUserName();
$user_role    = getCurrentUserRole();

$parts    = explode(' ', trim($user_name));
$initials = strtoupper(substr($parts[0], 0, 1));
if (isset($parts[1])) {
    $initials .= strtoupper(substr($parts[1], 0, 1));
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">

        <div class="d-flex align-items-center gap-2">
            <button class="btn-sidebar-toggle"
                    type="button"
                    data-bs-toggle="offcanvas"
                    data-bs-target="#sidebarOffcanvas"
                    aria-controls="sidebarOffcanvas"
                    aria-label="Apri menu">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M2.5 12a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5zm0-4a.5.5 0 0 1 .5-.5h10a.5.5 0 0 1 0 1H3a.5.5 0 0 1-.5-.5z"/>
                </svg>
            </button>

            <a class="navbar-brand" href="/dashboard.php">
                <img src="/assets/IMG/logo.png" alt="BiblioTech" class="navbar-logo">
                <span class="navbar-brand-text">BiblioTech</span>
            </a>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto ms-3">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>"
                       href="/dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'libri.php' ? 'active' : '' ?>"
                       href="/libri.php">Catalogo</a>
                </li>
                <?php if (isStudente()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'prestiti.php' ? 'active' : '' ?>"
                           href="/prestiti.php">I Miei Prestiti</a>
                    </li>
                <?php endif; ?>
                <?php if (isBibliotecario()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'gestione_libri.php' ? 'active' : '' ?>"
                           href="/gestione_libri.php">Gestione Libri</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?= $current_page === 'gestione_restituzioni.php' ? 'active' : '' ?>"
                           href="/gestione_restituzioni.php">Restituzioni</a>
                    </li>
                <?php endif; ?>
            </ul>

            <div class="navbar-user">
                <div class="navbar-avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="navbar-user-info">
                    <span class="navbar-user-name"><?= htmlspecialchars($user_name) ?></span>
                    <span class="navbar-user-role"><?= htmlspecialchars($user_role) ?></span>
                </div>
                <div class="navbar-divider d-none d-lg-block"></div>
                <a href="/logout.php" class="nav-link navbar-logout d-none d-lg-flex">
                    <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                        <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                    </svg>
                    Esci
                </a>
            </div>
        </div>

    </div>
</nav>

<div class="offcanvas offcanvas-sidebar offcanvas-start"
     tabindex="-1"
     id="sidebarOffcanvas"
     aria-labelledby="sidebarOffcanvasLabel">

    <div class="offcanvas-header">
        <div class="offcanvas-title" id="sidebarOffcanvasLabel">
            <img src="/assets/IMG/logo.png" alt="" style="height:24px; width:24px; object-fit:contain; border-radius:4px;">
            BiblioTech
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Chiudi"></button>
    </div>

    <div class="offcanvas-body">

        <div class="sidebar-nav-label">Navigazione</div>

        <a href="/dashboard.php"
           class="sidebar-nav-link <?= $current_page === 'dashboard.php' ? 'active' : '' ?>">
            <span class="sidebar-nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 4a.5.5 0 0 1 .5.5V6a.5.5 0 0 1-1 0V4.5A.5.5 0 0 1 8 4zM3.732 5.732a.5.5 0 0 1 .707 0l.915.914a.5.5 0 1 1-.708.708l-.914-.915a.5.5 0 0 1 0-.707zM2 10a.5.5 0 0 1 .5-.5h1.586a.5.5 0 0 1 0 1H2.5A.5.5 0 0 1 2 10zm9.5 0a.5.5 0 0 1 .5-.5h1.5a.5.5 0 0 1 0 1H12a.5.5 0 0 1-.5-.5zm.754-4.246a.389.389 0 0 0-.527-.02L7.547 9.31a.91.91 0 1 0 1.302 1.258l3.434-4.297a.389.389 0 0 0-.029-.517z"/>
                    <path fill-rule="evenodd" d="M0 10a8 8 0 1 1 15.547 2.661c-.442 1.253-1.845 1.602-2.932 1.25C11.309 13.488 9.475 13 8 13c-1.474 0-3.31.488-4.615.911-1.087.352-2.49.003-2.932-1.25A7.988 7.988 0 0 1 0 10zm8-7a7 7 0 0 0-6.603 9.329c.203.575.923.876 1.68.63C4.397 12.533 6.358 12 8 12s3.604.532 4.923.96c.757.245 1.477-.056 1.68-.631A7 7 0 0 0 8 3z"/>
                </svg>
            </span>
            Dashboard
        </a>

        <a href="/libri.php"
           class="sidebar-nav-link <?= $current_page === 'libri.php' ? 'active' : '' ?>">
            <span class="sidebar-nav-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M1 2.828c.885-.37 2.154-.769 3.388-.893 1.33-.134 2.458.063 3.112.752v9.746c-.935-.53-2.12-.603-3.213-.493-1.18.12-2.37.461-3.287.811V2.828zm7.5-.141c.654-.689 1.782-.886 3.112-.752 1.234.124 2.503.523 3.388.893v9.923c-.918-.35-2.107-.692-3.287-.81-1.094-.111-2.278-.039-3.213.492V2.687zM8 1.783C7.015.936 5.587.81 4.287.94c-1.514.153-3.042.672-3.994 1.105A.5.5 0 0 0 0 2.5v11a.5.5 0 0 0 .707.455c.882-.4 2.303-.881 3.68-1.02 1.409-.142 2.59.087 3.223.877a.5.5 0 0 0 .78 0c.633-.79 1.814-1.019 3.222-.877 1.378.139 2.8.62 3.681 1.02A.5.5 0 0 0 16 13.5v-11a.5.5 0 0 0-.293-.455c-.952-.433-2.48-.952-3.994-1.105C10.413.809 8.985.936 8 1.783z"/>
                </svg>
            </span>
            Catalogo Libri
        </a>

        <?php if (isStudente()): ?>
            <a href="/prestiti.php"
               class="sidebar-nav-link <?= $current_page === 'prestiti.php' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v13.5a.5.5 0 0 1-.777.416L8 13.101l-5.223 2.815A.5.5 0 0 1 2 15.5V2zm2-1a1 1 0 0 0-1 1v12.566l4.723-2.482a.5.5 0 0 1 .554 0L13 14.566V2a1 1 0 0 0-1-1H4z"/>
                    </svg>
                </span>
                I Miei Prestiti
            </a>
        <?php endif; ?>

        <?php if (isBibliotecario()): ?>
            <div class="sidebar-nav-label">Amministrazione</div>
            <a href="/gestione_libri.php"
               class="sidebar-nav-link <?= in_array($current_page, ['gestione_libri.php','libro_form.php']) ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2z"/>
                    </svg>
                </span>
                Gestione Libri
            </a>
            <a href="/gestione_restituzioni.php"
               class="sidebar-nav-link <?= $current_page === 'gestione_restituzioni.php' ? 'active' : '' ?>">
                <span class="sidebar-nav-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" fill="currentColor" viewBox="0 0 16 16">
                        <path fill-rule="evenodd" d="M1 8a7 7 0 1 0 14 0A7 7 0 0 0 1 8zm15 0A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-4.5-.5a.5.5 0 0 1 0 1H5.707l2.147 2.146a.5.5 0 0 1-.708.708l-3-3a.5.5 0 0 1 0-.708l3-3a.5.5 0 1 1 .708.708L5.707 7.5H11.5z"/>
                    </svg>
                </span>
                Restituzioni
            </a>
        <?php endif; ?>

        <div class="sidebar-user-footer">
            <div class="sidebar-user-block">
                <div class="sidebar-avatar"><?= htmlspecialchars($initials) ?></div>
                <div>
                    <div class="sidebar-user-name"><?= htmlspecialchars($user_name) ?></div>
                    <div class="sidebar-user-role"><?= htmlspecialchars($user_role) ?></div>
                </div>
            </div>
            <a href="/logout.php" class="sidebar-logout-btn">
                <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" fill="currentColor" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M10 12.5a.5.5 0 0 1-.5.5h-8a.5.5 0 0 1-.5-.5v-9a.5.5 0 0 1 .5-.5h8a.5.5 0 0 1 .5.5v2a.5.5 0 0 0 1 0v-2A1.5 1.5 0 0 0 9.5 2h-8A1.5 1.5 0 0 0 0 3.5v9A1.5 1.5 0 0 0 1.5 14h8a1.5 1.5 0 0 0 1.5-1.5v-2a.5.5 0 0 0-1 0v2z"/>
                    <path fill-rule="evenodd" d="M15.854 8.354a.5.5 0 0 0 0-.708l-3-3a.5.5 0 0 0-.708.708L14.293 7.5H5.5a.5.5 0 0 0 0 1h8.793l-2.147 2.146a.5.5 0 0 0 .708.708l3-3z"/>
                </svg>
                Esci dall'account
            </a>
        </div>

    </div>
</div>