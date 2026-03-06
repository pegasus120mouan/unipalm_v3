<?php
require_once '../inc/functions/connexion.php';
include('header.php');

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
?>

<style>
/* Variables CSS */
:root {
    --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    --success-gradient: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    --danger-gradient: linear-gradient(135deg, #eb3349 0%, #f45c43 100%);
    --warning-gradient: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    --dark-gradient: linear-gradient(135deg, #2c3e50 0%, #4ca1af 100%);
    --card-shadow: 0 10px 40px rgba(0,0,0,0.1);
    --card-hover-shadow: 0 20px 60px rgba(0,0,0,0.15);
    --border-radius: 16px;
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

/* Page Header */
.page-header-edit {
    background: var(--primary-gradient);
    border-radius: var(--border-radius);
    padding: 30px 40px;
    margin-bottom: 30px;
    color: white;
    box-shadow: var(--card-shadow);
    position: relative;
    overflow: hidden;
}

.page-header-edit::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    background: rgba(255,255,255,0.1);
    border-radius: 50%;
}

.page-header-edit::after {
    content: '';
    position: absolute;
    bottom: -60%;
    left: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255,255,255,0.05);
    border-radius: 50%;
}

.page-header-edit h1 {
    margin: 0;
    font-size: 1.8rem;
    font-weight: 700;
    position: relative;
    z-index: 1;
}

.page-header-edit h1 i {
    margin-right: 15px;
    opacity: 0.9;
}

.btn-back {
    background: rgba(255,255,255,0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255,255,255,0.3);
    color: white;
    padding: 12px 25px;
    border-radius: 50px;
    font-weight: 600;
    transition: var(--transition);
    position: relative;
    z-index: 1;
}

.btn-back:hover {
    background: rgba(255,255,255,0.3);
    color: white;
    transform: translateX(-5px);
}

/* Container */
.edit-container {
    max-width: 1400px;
    margin: 0 auto;
    padding: 0 15px;
}

/* Cards */
.edit-card {
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
    margin-bottom: 25px;
    overflow: hidden;
    transition: var(--transition);
    border: none;
}

.edit-card:hover {
    transform: translateY(-5px);
    box-shadow: var(--card-hover-shadow);
}

.edit-card-header {
    padding: 20px 25px;
    font-weight: 700;
    font-size: 1.1rem;
    display: flex;
    align-items: center;
    border-bottom: none;
}

.edit-card-header.identification {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.edit-card-header.identite {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.edit-card-header.exploitation {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.edit-card-header i {
    margin-right: 12px;
    font-size: 1.2rem;
    opacity: 0.9;
}

.edit-card-body {
    padding: 30px 25px;
}

/* Form Elements */
.form-group {
    margin-bottom: 20px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 10px;
    font-size: 0.9rem;
    display: flex;
    align-items: center;
}

.form-group label i {
    margin-right: 8px;
    color: #667eea;
    font-size: 0.85rem;
}

.form-control {
    border-radius: 12px;
    border: 2px solid #e9ecef;
    padding: 14px 18px;
    font-size: 0.95rem;
    transition: var(--transition);
    background: #f8f9fa;
}

.form-control:focus {
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.15);
}

.form-control:hover:not(:focus) {
    border-color: #ced4da;
    background: white;
}

.form-control[readonly] {
    background: #e9ecef;
    color: #6c757d;
    cursor: not-allowed;
}

select.form-control {
    appearance: none;
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23667eea' d='M6 8L1 3h10z'/%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 15px center;
    padding-right: 40px;
}

/* Buttons */
.btn-actions {
    display: flex;
    justify-content: center;
    gap: 20px;
    padding: 30px 0;
}

.btn-save {
    background: var(--success-gradient);
    border: none;
    color: white;
    padding: 16px 50px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    transition: var(--transition);
    box-shadow: 0 10px 30px rgba(17, 153, 142, 0.3);
}

.btn-save:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(17, 153, 142, 0.4);
    color: white;
}

.btn-save:active {
    transform: translateY(-1px);
}

.btn-cancel {
    background: linear-gradient(135deg, #636e72 0%, #2d3436 100%);
    border: none;
    color: white;
    padding: 16px 50px;
    border-radius: 50px;
    font-weight: 700;
    font-size: 1rem;
    transition: var(--transition);
    box-shadow: 0 10px 30px rgba(45, 52, 54, 0.3);
}

.btn-cancel:hover {
    transform: translateY(-3px);
    box-shadow: 0 15px 40px rgba(45, 52, 54, 0.4);
    color: white;
}

/* Loader */
.loader-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 80px;
    background: white;
    border-radius: var(--border-radius);
    box-shadow: var(--card-shadow);
}

.loader-spinner {
    width: 60px;
    height: 60px;
    border: 4px solid #f3f3f3;
    border-top: 4px solid #667eea;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.loader-container p {
    margin-top: 20px;
    color: #6c757d;
    font-weight: 500;
}

/* Alerts */
.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 350px;
    border-radius: 12px;
    border: none;
    box-shadow: 0 10px 40px rgba(0,0,0,0.2);
    animation: slideIn 0.4s cubic-bezier(0.4, 0, 0.2, 1);
}

.alert-floating.alert-success {
    background: var(--success-gradient);
    color: white;
}

.alert-floating.alert-danger {
    background: var(--danger-gradient);
    color: white;
}

@keyframes slideIn {
    from { transform: translateX(120%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    .page-header-edit {
        padding: 20px;
    }
    
    .page-header-edit h1 {
        font-size: 1.4rem;
    }
    
    .btn-actions {
        flex-direction: column;
        padding: 20px;
    }
    
    .btn-save, .btn-cancel {
        width: 100%;
    }
}

/* Animation d'entrée */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.edit-card {
    animation: fadeInUp 0.5s ease-out;
}

.edit-card:nth-child(1) { animation-delay: 0.1s; }
.edit-card:nth-child(2) { animation-delay: 0.2s; }
.edit-card:nth-child(3) { animation-delay: 0.3s; }
</style>

<section class="content">
    <div class="container-fluid edit-container">
        <!-- Page Header -->
        <div class="page-header-edit d-flex justify-content-between align-items-center">
            <h1><i class="fas fa-user-edit"></i>Modifier le planteur</h1>
            <a href="plantations.php" class="btn btn-back">
                <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
            </a>
        </div>

        <!-- Loader -->
        <div id="editLoader" class="loader-container">
            <div class="loader-spinner"></div>
            <p>Chargement des informations...</p>
        </div>

        <!-- Error -->
        <div id="editError" class="alert alert-danger" style="display:none; border-radius: 12px;"></div>

        <!-- Form -->
        <form id="editForm" style="display:none;">
            <input type="hidden" id="planteurId" name="id" value="">

            <div class="row">
                <!-- Colonne gauche - Identification -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header identification">
                            <i class="fas fa-id-card"></i>Identification
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label><i class="fas fa-hashtag"></i>Numéro de fiche</label>
                                <input type="text" class="form-control" id="numero_fiche" name="numero_fiche" readonly>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-id-badge"></i>Pièce d'identité</label>
                                <input type="text" class="form-control" id="piece_identite" name="piece_identite" placeholder="Numéro de la pièce">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne centrale - Identité -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header identite">
                            <i class="fas fa-user"></i>Identité
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label><i class="fas fa-user-circle"></i>Nom et prénoms <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom_prenoms" name="nom_prenoms" required placeholder="Nom complet du planteur">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-phone"></i>Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone" placeholder="Numéro de téléphone">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-calendar-alt"></i>Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-map-pin"></i>Lieu de naissance</label>
                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance" placeholder="Ville ou village de naissance">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-heart"></i>Situation matrimoniale</label>
                                <select class="form-control" id="situation_matrimoniale" name="situation_matrimoniale">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Célibataire">Célibataire</option>
                                    <option value="Marié(e)">Marié(e)</option>
                                    <option value="Divorcé(e)">Divorcé(e)</option>
                                    <option value="Veuf(ve)">Veuf(ve)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-child"></i>Nombre d'enfants</label>
                                <input type="number" class="form-control" id="nombre_enfants" name="nombre_enfants" min="0" placeholder="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite - Exploitation -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header exploitation">
                            <i class="fas fa-map-marked-alt"></i>Exploitation
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label><i class="fas fa-globe-africa"></i>Région</label>
                                <input type="text" class="form-control" id="region" name="region" placeholder="Région d'exploitation">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-city"></i>Sous-préfecture</label>
                                <input type="text" class="form-control" id="sous_prefecture_village" name="sous_prefecture_village" placeholder="Sous-préfecture">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-home"></i>Village</label>
                                <input type="text" class="form-control" id="village" name="village" placeholder="Village d'exploitation">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-location-arrow"></i>Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Ex: 5.345678">
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-location-arrow"></i>Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Ex: -4.012345">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="btn-actions">
                <button type="button" class="btn btn-cancel" onclick="window.location.href='plantations.php'">
                    <i class="fas fa-times mr-2"></i>Annuler
                </button>
                <button type="submit" class="btn btn-save">
                    <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                </button>
            </div>
        </form>
    </div>
</section>

<script>
(function() {
    const planteurId = <?= json_encode($id) ?>;
    const apiBase = '../inc/functions/requete/api_requete_planteurs.php';
    const defaultPhoto = '../dist/img/default-avatar.png';

    const loaderEl = document.getElementById('editLoader');
    const errorEl = document.getElementById('editError');
    const formEl = document.getElementById('editForm');

    function showError(msg) {
        errorEl.textContent = msg;
        errorEl.style.display = 'block';
        loaderEl.style.display = 'none';
        formEl.style.display = 'none';
    }

    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-floating alert-dismissible fade show`;
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        `;
        document.body.appendChild(alertDiv);
        setTimeout(() => alertDiv.remove(), 5000);
    }

    function formatDateForInput(dateStr) {
        if (!dateStr) return '';
        const d = new Date(dateStr);
        if (isNaN(d.getTime())) return '';
        return d.toISOString().split('T')[0];
    }

    function buildPhotoUrl(url) {
        if (!url) return defaultPhoto;
        const isHttps = window.location.protocol === 'https:';
        if (isHttps && url.startsWith('http://')) {
            return '../inc/functions/requete/proxy_image.php?url=' + encodeURIComponent(url);
        }
        return url;
    }

    function fillForm(planteur) {
        document.getElementById('planteurId').value = planteur.id || '';
        document.getElementById('numero_fiche').value = planteur.numero_fiche || '';
        document.getElementById('nom_prenoms').value = planteur.nom_prenoms || '';
        document.getElementById('telephone').value = planteur.telephone || '';
        document.getElementById('piece_identite').value = planteur.piece_identite || '';
        document.getElementById('date_naissance').value = formatDateForInput(planteur.date_naissance);
        document.getElementById('lieu_naissance').value = planteur.lieu_naissance || '';
        document.getElementById('situation_matrimoniale').value = planteur.situation_matrimoniale || '';
        document.getElementById('nombre_enfants').value = planteur.nombre_enfants || '';

        const expl = planteur.exploitation || {};
        document.getElementById('region').value = expl.region || '';
        document.getElementById('sous_prefecture_village').value = expl.sous_prefecture_village || '';
        document.getElementById('village').value = expl.village || '';
        document.getElementById('latitude').value = expl.latitude || '';
        document.getElementById('longitude').value = expl.longitude || '';
    }

    async function loadPlanteur() {
        if (!planteurId) {
            showError('ID du planteur manquant.');
            return;
        }

        try {
            const res = await fetch(`${apiBase}?action=planteurs&id=${encodeURIComponent(planteurId)}`, { cache: 'no-store' });
            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.error || json.message || 'Erreur API');
            }

            const planteur = json.data?.planteurs?.[0] || json.data;
            if (!planteur || !planteur.id) {
                throw new Error('Planteur introuvable.');
            }

            fillForm(planteur);
            loaderEl.style.display = 'none';
            formEl.style.display = 'block';

        } catch (e) {
            showError(e.message || String(e));
        }
    }

    formEl.addEventListener('submit', async function(e) {
        e.preventDefault();

        const submitBtn = formEl.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enregistrement...';

        try {
            const formData = new FormData(formEl);
            const data = {
                action: 'update_planteur',
                id: formData.get('id'),
                nom_prenoms: formData.get('nom_prenoms'),
                telephone: formData.get('telephone'),
                piece_identite: formData.get('piece_identite'),
                date_naissance: formData.get('date_naissance'),
                lieu_naissance: formData.get('lieu_naissance'),
                situation_matrimoniale: formData.get('situation_matrimoniale'),
                nombre_enfants: formData.get('nombre_enfants'),
                exploitation: {
                    region: formData.get('region'),
                    sous_prefecture_village: formData.get('sous_prefecture_village'),
                    village: formData.get('village'),
                    latitude: formData.get('latitude'),
                    longitude: formData.get('longitude')
                }
            };

            const res = await fetch(apiBase, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                throw new Error(json.error || json.message || 'Erreur lors de la mise à jour');
            }

            showAlert('success', '<i class="fas fa-check-circle mr-2"></i>Planteur modifié avec succès !');
            
            setTimeout(() => {
                window.location.href = 'plantations.php';
            }, 1500);

        } catch (e) {
            showAlert('danger', '<i class="fas fa-exclamation-circle mr-2"></i>' + (e.message || String(e)));
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;
        }
    });

    loadPlanteur();
})();
</script>

<?php include('footer.php'); ?>
