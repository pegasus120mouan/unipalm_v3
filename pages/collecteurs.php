<?php
include('header.php');
?>

<style>
.table-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.table thead th {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
    padding: 12px 15px;
    font-weight: 600;
    font-size: 0.9rem;
}
.table thead th:first-child {
    border-radius: 8px 0 0 0;
}
.table thead th:last-child {
    border-radius: 0 8px 0 0;
}
.table tbody tr {
    transition: background 0.2s;
}
.table tbody tr:hover {
    background: #f8f9fa;
}
.table tbody td {
    vertical-align: middle;
    padding: 12px 15px;
    border-bottom: 1px solid #eee;
}
.avatar-cell {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
}
.role-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.75rem;
    font-weight: 600;
}
.role-admin { background: #e74c3c; color: white; }
.role-directeur { background: #9b59b6; color: white; }
.role-operateur { background: #3498db; color: white; }
.role-caissiere { background: #1abc9c; color: white; }
.role-collecteur { background: #2ecc71; color: white; }
.role-default { background: #95a5a6; color: white; }
.login-badge {
    display: inline-block;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 500;
    background: #3498db;
    color: white;
}
.status-badge {
    display: inline-block;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
}
.status-actif { background: #d4edda; color: #155724; }
.status-inactif { background: #f8d7da; color: #721c24; }
.action-btn {
    width: 32px;
    height: 32px;
    border-radius: 6px;
    border: none;
    margin: 0 2px;
    cursor: pointer;
    transition: transform 0.2s;
}
.action-btn:hover {
    transform: scale(1.1);
}
.action-btn.edit { background: #3498db; color: white; }
.action-btn.delete { background: #e74c3c; color: white; }
.search-container {
    background: white;
    border-radius: 12px;
    padding: 20px;
    margin-bottom: 20px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.05);
}
.loader-container {
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 300px;
}
.loader-spinner {
    width: 50px;
    height: 50px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-users mr-2"></i>Liste des collecteurs</h1>
            </div>
            <div class="col-sm-6 text-right">
                <button type="button" class="btn btn-success" data-toggle="modal" data-target="#createUserModal">
                    <i class="fas fa-user-plus mr-2"></i>Créer un collecteur
                </button>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        

        <div class="search-container">
            <div class="row">
                <div class="col-md-4">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="text" id="searchInput" class="form-control" placeholder="Rechercher un collecteur...">
                    </div>
                </div>
                <div class="col-md-3">
                    <select id="roleFilter" class="form-control">
                        <option value="">Tous les rôles</option>
                        <option value="collecteur">Collecteurs</option>
                        <option value="admin">Administrateurs</option>
                        <option value="superviseur">Superviseurs</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="statusFilter" class="form-control">
                        <option value="">Tous les statuts</option>
                        <option value="1">Actifs</option>
                        <option value="0">Inactifs</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button id="refreshBtn" class="btn btn-primary btn-block">
                        <i class="fas fa-sync-alt mr-1"></i>Actualiser
                    </button>
                </div>
            </div>
        </div>

        <div id="errorAlert" class="alert alert-danger" style="display:none;"></div>

        <div id="loader" class="loader-container">
            <div class="text-center">
                <div class="loader-spinner mb-3"></div>
                <p class="text-muted">Chargement des collecteurs...</p>
            </div>
        </div>

        <div class="table-container" id="tableContainer" style="display:none;">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th><i class="fas fa-user mr-1"></i>Nom</th>
                            <th><i class="fas fa-id-card mr-1"></i>Prénoms</th>
                            <th><i class="fas fa-phone mr-1"></i>Contact</th>
                            <th><i class="fas fa-tag mr-1"></i>Rôle</th>
                            <th><i class="fas fa-sign-in-alt mr-1"></i>Login</th>
                            <th><i class="fas fa-image mr-1"></i>Avatar</th>
                            <th><i class="fas fa-cogs mr-1"></i>Actions</th>
                            <th><i class="fas fa-toggle-on mr-1"></i>Statut</th>
                        </tr>
                    </thead>
                    <tbody id="collecteursTbody"></tbody>
                </table>
            </div>
        </div>

        <div id="noResults" class="text-center py-5" style="display:none;">
            <i class="fas fa-users-slash fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">Aucun collecteur trouvé</h5>
        </div>

    </div>
</section>

<!-- Modal Créer un utilisateur -->
<div class="modal fade" id="createUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i>Enregistrer un collecteur</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="createUserForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-1"></i>Nom</label>
                                <input type="text" class="form-control" id="userNom" name="nom" placeholder="Nom" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-1"></i>Prénoms</label>
                                <input type="text" class="form-control" id="userPrenoms" name="prenoms" placeholder="Prénoms" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-phone mr-1"></i>Contact</label>
                                <input type="text" class="form-control" id="userContact" name="contact" placeholder="Contact" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-sign-in-alt mr-1"></i>Login</label>
                                <input type="text" class="form-control" id="userLogin" name="login" placeholder="Login" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock mr-1"></i>Mot de passe</label>
                                <input type="password" class="form-control" id="userPassword" name="password" placeholder="Mot de passe" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock mr-1"></i>Confirmation</label>
                                <input type="password" class="form-control" id="userPasswordConfirm" name="password_confirm" placeholder="Confirmer le mot de passe" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-12">
                            <div class="form-group">
                                <label><i class="fas fa-users mr-1"></i>Rôle</label>
                                <select class="form-control" id="userRole" name="role" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="collecteur">Collecteur</option>
                                    <option value="operateur">Opérateur</option>
                                    <option value="caissiere">Caissière</option>
                                    <option value="directeur">Directeur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div id="createUserError" class="alert alert-danger" style="display:none;"></div>
                    <div id="createUserSuccess" class="alert alert-success" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                        <i class="fas fa-save mr-1"></i>Enregistrer
                    </button>
                    <button type="button" class="btn btn-danger" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmation Suppression -->
<div class="modal fade" id="deleteConfirmModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-2"></i>Confirmation de suppression</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-center py-4">
                <i class="fas fa-user-times fa-4x text-danger mb-3"></i>
                <h5>Êtes-vous sûr de vouloir supprimer</h5>
                <h4 class="text-danger font-weight-bold" id="deleteUserName"></h4>
                <p class="text-muted mt-3">Cette action est irréversible.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary px-4" data-dismiss="modal">
                    <i class="fas fa-times mr-1"></i>Annuler
                </button>
                <button type="button" class="btn btn-danger px-4" id="confirmDeleteBtn">
                    <i class="fas fa-trash mr-1"></i>Supprimer
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Succès Suppression -->
<div class="modal fade" id="deleteSuccessModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-check-circle fa-5x text-success"></i>
                </div>
                <h4 class="text-success">Supprimé avec succès !</h4>
                <p class="text-muted" id="deleteSuccessMessage">L'utilisateur a été supprimé.</p>
            </div>
        </div>
    </div>
</div>

<!-- Modal Erreur Suppression -->
<div class="modal fade" id="deleteErrorModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-body text-center py-5">
                <div class="mb-4">
                    <i class="fas fa-times-circle fa-5x text-danger"></i>
                </div>
                <h4 class="text-danger">Erreur !</h4>
                <p class="text-muted" id="deleteErrorMessage">Une erreur est survenue.</p>
                <button type="button" class="btn btn-secondary mt-3" data-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Modifier un utilisateur -->
<div class="modal fade" id="editUserModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%); color: white;">
                <h5 class="modal-title"><i class="fas fa-user-edit mr-2"></i>Modifier l'utilisateur</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="editUserForm">
                <input type="hidden" id="editUserId" name="id">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-1"></i>Nom</label>
                                <input type="text" class="form-control" id="editUserNom" name="nom" placeholder="Nom" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-user mr-1"></i>Prénoms</label>
                                <input type="text" class="form-control" id="editUserPrenoms" name="prenoms" placeholder="Prénoms" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-phone mr-1"></i>Contact</label>
                                <input type="text" class="form-control" id="editUserContact" name="contact" placeholder="Contact" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-sign-in-alt mr-1"></i>Login</label>
                                <input type="text" class="form-control" id="editUserLogin" name="login" placeholder="Login" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-users mr-1"></i>Rôle</label>
                                <select class="form-control" id="editUserRole" name="role" required>
                                    <option value="">Sélectionner un rôle</option>
                                    <option value="collecteur">Collecteur</option>
                                    <option value="operateur">Opérateur</option>
                                    <option value="caissiere">Caissière</option>
                                    <option value="directeur">Directeur</option>
                                    <option value="admin">Administrateur</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-toggle-on mr-1"></i>Statut</label>
                                <select class="form-control" id="editUserStatut" name="statut_compte">
                                    <option value="1">Actif</option>
                                    <option value="0">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <hr>
                    <p class="text-muted small"><i class="fas fa-info-circle mr-1"></i>Laissez vide pour conserver le mot de passe actuel</p>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock mr-1"></i>Nouveau mot de passe</label>
                                <input type="password" class="form-control" id="editUserPassword" name="password" placeholder="Nouveau mot de passe">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label><i class="fas fa-lock mr-1"></i>Confirmation</label>
                                <input type="password" class="form-control" id="editUserPasswordConfirm" name="password_confirm" placeholder="Confirmer le mot de passe">
                            </div>
                        </div>
                    </div>
                    <div id="editUserError" class="alert alert-danger" style="display:none;"></div>
                    <div id="editUserSuccess" class="alert alert-success" style="display:none;"></div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn text-white" style="background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);">
                        <i class="fas fa-save mr-1"></i>Enregistrer
                    </button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i>Annuler
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include('footer.php'); ?>

<script>
(function() {
    const apiUrl = '../inc/functions/requete/api_requete_utilisateurs.php';
    const isHttps = window.location.protocol === 'https:';
    
    const defaultAvatarSvg = `<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 80 80">
        <rect width="80" height="80" rx="40" fill="#E9ECEF"/>
        <circle cx="40" cy="32" r="14" fill="#ADB5BD"/>
        <path d="M16 70c4-14 18-22 24-22s20 8 24 22" fill="#ADB5BD"/>
    </svg>`;
    const defaultAvatar = `data:image/svg+xml;utf8,${encodeURIComponent(defaultAvatarSvg)}`;

    let allUsers = [];

    const loaderEl = document.getElementById('loader');
    const tableContainerEl = document.getElementById('tableContainer');
    const tbodyEl = document.getElementById('collecteursTbody');
    const errorEl = document.getElementById('errorAlert');
    const noResultsEl = document.getElementById('noResults');
    const searchEl = document.getElementById('searchInput');
    const roleFilterEl = document.getElementById('roleFilter');
    const statusFilterEl = document.getElementById('statusFilter');
    const refreshBtn = document.getElementById('refreshBtn');

    function escapeHtml(v) {
        return String(v ?? '')
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }

    function getAvatarUrl(user) {
        const url = user?.avatar_url;
        if (!url) return defaultAvatar;
        if (isHttps && url.startsWith('http://')) {
            return '../inc/functions/requete/proxy_image.php?url=' + encodeURIComponent(url);
        }
        return url;
    }

    function getRoleClass(role) {
        const r = String(role || '').toLowerCase();
        if (r === 'collecteur') return 'role-collecteur';
        if (r === 'admin' || r === 'administrateur') return 'role-admin';
        if (r === 'directeur') return 'role-directeur';
        if (r === 'operateur') return 'role-operateur';
        if (r === 'caissiere') return 'role-caissiere';
        return 'role-default';
    }

    function renderRow(user) {
        const avatarUrl = getAvatarUrl(user);
        const roleClass = getRoleClass(user.role);
        const statusClass = user.statut_compte ? 'status-actif' : 'status-inactif';
        const statusText = user.statut_compte ? 'Actif' : 'Inactif';

        return `
            <tr>
                <td>${escapeHtml(user.nom || '')}</td>
                <td>${escapeHtml(user.prenoms || '')}</td>
                <td><i class="fas fa-phone mr-1 text-muted"></i>${escapeHtml(user.contact || '')}</td>
                <td><span class="role-badge ${roleClass}">${escapeHtml(user.role || 'N/A')}</span></td>
                <td><span class="login-badge">${escapeHtml(user.login || '').toUpperCase()}</span></td>
                <td>
                    <img src="${escapeHtml(avatarUrl)}" 
                         alt="Avatar" 
                         class="avatar-cell"
                         onerror="this.onerror=null;this.src='${escapeHtml(defaultAvatar)}';">
                </td>
                <td>
                    <button type="button" class="action-btn edit" data-id="${escapeHtml(user.id)}" title="Modifier">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="action-btn delete" data-id="${escapeHtml(user.id)}" title="Supprimer">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
                <td><span class="status-badge ${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }

    function render(users) {
        if (!users.length) {
            tableContainerEl.style.display = 'none';
            noResultsEl.style.display = 'block';
            return;
        }
        noResultsEl.style.display = 'none';
        tableContainerEl.style.display = 'block';
        tbodyEl.innerHTML = users.map(renderRow).join('');
    }

    function filterUsers() {
        const search = searchEl.value.toLowerCase().trim();
        const role = roleFilterEl.value.toLowerCase();
        const status = statusFilterEl.value;

        const filtered = allUsers.filter(user => {
            const name = (user.nom_complet || user.nom || '').toLowerCase();
            const contact = (user.contact || '').toLowerCase();
            const login = (user.login || '').toLowerCase();
            const userRole = (user.role || '').toLowerCase();
            const userStatus = user.statut_compte ? '1' : '0';

            const matchSearch = !search || name.includes(search) || contact.includes(search) || login.includes(search);
            const matchRole = !role || userRole === role;
            const matchStatus = status === '' || userStatus === status;

            return matchSearch && matchRole && matchStatus;
        });

        render(filtered);
    }

    async function loadCollecteurs() {
        loaderEl.style.display = 'flex';
        tableContainerEl.style.display = 'none';
        errorEl.style.display = 'none';
        noResultsEl.style.display = 'none';

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Erreur API');

            allUsers = result.data?.utilisateurs || [];
            render(allUsers);

        } catch (err) {
            errorEl.textContent = 'Erreur: ' + err.message;
            errorEl.style.display = 'block';
        } finally {
            loaderEl.style.display = 'none';
        }
    }

    searchEl.addEventListener('input', filterUsers);
    roleFilterEl.addEventListener('change', filterUsers);
    statusFilterEl.addEventListener('change', filterUsers);
    refreshBtn.addEventListener('click', loadCollecteurs);

    // Gestion du formulaire de création
    const createForm = document.getElementById('createUserForm');
    const createErrorEl = document.getElementById('createUserError');
    const createSuccessEl = document.getElementById('createUserSuccess');

    createForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        createErrorEl.style.display = 'none';
        createSuccessEl.style.display = 'none';

        const password = document.getElementById('userPassword').value;
        const passwordConfirm = document.getElementById('userPasswordConfirm').value;

        if (password !== passwordConfirm) {
            createErrorEl.textContent = 'Les mots de passe ne correspondent pas.';
            createErrorEl.style.display = 'block';
            return;
        }

        const formData = new FormData(createForm);
        const data = Object.fromEntries(formData.entries());
        delete data.password_confirm;

        try {
            const response = await fetch('../inc/functions/requete/api_create_utilisateur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Erreur lors de la création');
            }

            createSuccessEl.textContent = 'Utilisateur créé avec succès !';
            createSuccessEl.style.display = 'block';
            createForm.reset();

            setTimeout(() => {
                $('#createUserModal').modal('hide');
                createSuccessEl.style.display = 'none';
                loadCollecteurs();
            }, 1500);

        } catch (err) {
            createErrorEl.textContent = err.message;
            createErrorEl.style.display = 'block';
        }
    });

    // Reset form on modal close
    $('#createUserModal').on('hidden.bs.modal', function() {
        createForm.reset();
        createErrorEl.style.display = 'none';
        createSuccessEl.style.display = 'none';
    });

    // Gestion de la suppression avec modals
    let deleteUserId = null;
    let deleteUserRow = null;

    // Ouvrir le modal de confirmation
    document.addEventListener('click', function(e) {
        const deleteBtn = e.target.closest('.action-btn.delete');
        if (!deleteBtn) return;

        deleteUserId = deleteBtn.dataset.id;
        deleteUserRow = deleteBtn.closest('tr');
        const userName = deleteUserRow ? deleteUserRow.cells[0].textContent + ' ' + deleteUserRow.cells[1].textContent : 'cet utilisateur';

        document.getElementById('deleteUserName').textContent = userName;
        $('#deleteConfirmModal').modal('show');
    });

    // Confirmer la suppression
    document.getElementById('confirmDeleteBtn').addEventListener('click', async function() {
        if (!deleteUserId) return;

        const btn = this;
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Suppression...';

        try {
            const response = await fetch('../inc/functions/requete/api_delete_utilisateur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id: deleteUserId })
            });

            const result = await response.json();

            $('#deleteConfirmModal').modal('hide');

            if (!result.success) {
                throw new Error(result.error || 'Erreur lors de la suppression');
            }

            // Afficher le modal de succès
            const userName = document.getElementById('deleteUserName').textContent;
            document.getElementById('deleteSuccessMessage').textContent = userName + ' a été supprimé.';
            $('#deleteSuccessModal').modal('show');

            // Fermer automatiquement après 2 secondes
            setTimeout(() => {
                $('#deleteSuccessModal').modal('hide');
            }, 2000);

            // Recharger la liste
            loadCollecteurs();

        } catch (err) {
            $('#deleteConfirmModal').modal('hide');
            document.getElementById('deleteErrorMessage').textContent = err.message;
            $('#deleteErrorModal').modal('show');
        } finally {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-trash mr-1"></i>Supprimer';
            deleteUserId = null;
            deleteUserRow = null;
        }
    });

    // Reset on modal close
    $('#deleteConfirmModal').on('hidden.bs.modal', function() {
        document.getElementById('confirmDeleteBtn').disabled = false;
        document.getElementById('confirmDeleteBtn').innerHTML = '<i class="fas fa-trash mr-1"></i>Supprimer';
    });

    // ========== GESTION DE LA MODIFICATION ==========
    
    // Ouvrir le modal d'édition
    document.addEventListener('click', function(e) {
        const editBtn = e.target.closest('.action-btn.edit');
        if (!editBtn) return;

        const userId = editBtn.dataset.id;
        
        // Trouver l'utilisateur dans allUsers
        const user = allUsers.find(u => String(u.id) === String(userId));
        if (!user) {
            alert('Utilisateur non trouvé');
            return;
        }

        // Remplir le formulaire
        document.getElementById('editUserId').value = user.id;
        document.getElementById('editUserNom').value = user.nom || '';
        document.getElementById('editUserPrenoms').value = user.prenoms || '';
        document.getElementById('editUserContact').value = user.contact || '';
        document.getElementById('editUserLogin').value = user.login || '';
        document.getElementById('editUserRole').value = user.role || '';
        document.getElementById('editUserStatut').value = user.statut_compte ? '1' : '0';
        document.getElementById('editUserPassword').value = '';
        document.getElementById('editUserPasswordConfirm').value = '';

        // Reset messages
        document.getElementById('editUserError').style.display = 'none';
        document.getElementById('editUserSuccess').style.display = 'none';

        $('#editUserModal').modal('show');
    });

    // Soumettre le formulaire d'édition
    const editForm = document.getElementById('editUserForm');
    const editErrorEl = document.getElementById('editUserError');
    const editSuccessEl = document.getElementById('editUserSuccess');

    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        editErrorEl.style.display = 'none';
        editSuccessEl.style.display = 'none';

        const password = document.getElementById('editUserPassword').value;
        const passwordConfirm = document.getElementById('editUserPasswordConfirm').value;

        // Vérifier les mots de passe si renseignés
        if (password && password !== passwordConfirm) {
            editErrorEl.textContent = 'Les mots de passe ne correspondent pas.';
            editErrorEl.style.display = 'block';
            return;
        }

        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());
        delete data.password_confirm;

        // Ne pas envoyer le mot de passe s'il est vide
        if (!data.password) {
            delete data.password;
        }

        // Convertir statut en entier
        data.statut_compte = parseInt(data.statut_compte);

        const submitBtn = editForm.querySelector('button[type="submit"]');
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Enregistrement...';

        try {
            const response = await fetch('../inc/functions/requete/api_update_utilisateur.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (!result.success) {
                throw new Error(result.error || 'Erreur lors de la modification');
            }

            editSuccessEl.textContent = 'Utilisateur modifié avec succès !';
            editSuccessEl.style.display = 'block';

            setTimeout(() => {
                $('#editUserModal').modal('hide');
                editSuccessEl.style.display = 'none';
                loadCollecteurs();
            }, 1500);

        } catch (err) {
            editErrorEl.textContent = err.message;
            editErrorEl.style.display = 'block';
        } finally {
            submitBtn.disabled = false;
            submitBtn.innerHTML = '<i class="fas fa-save mr-1"></i>Enregistrer';
        }
    });

    // Reset form on modal close
    $('#editUserModal').on('hidden.bs.modal', function() {
        editForm.reset();
        editErrorEl.style.display = 'none';
        editSuccessEl.style.display = 'none';
    });

    loadCollecteurs();
})();
</script>
