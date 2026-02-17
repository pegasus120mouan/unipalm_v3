<?php
require_once '../inc/functions/connexion.php';
include('header.php');

$id = isset($_GET['id']) ? trim((string)$_GET['id']) : '';
?>

<style>
.edit-container {
    max-width: 1200px;
    margin: 0 auto;
}

.edit-card {
    background: white;
    border-radius: 10px;
    box-shadow: 0 2px 15px rgba(0,0,0,0.1);
    margin-bottom: 20px;
    overflow: hidden;
}

.edit-card-header {
    background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
    color: white;
    padding: 15px 20px;
    font-weight: 600;
    font-size: 1.1rem;
}

.edit-card-header i {
    margin-right: 10px;
}

.edit-card-body {
    padding: 25px;
}

.form-group label {
    font-weight: 600;
    color: #2c3e50;
    margin-bottom: 8px;
}

.form-control {
    border-radius: 8px;
    border: 1px solid #dee2e6;
    padding: 12px 15px;
    transition: all 0.3s ease;
}

.form-control:focus {
    border-color: #3498db;
    box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.15);
}

.photo-preview {
    width: 150px;
    height: 150px;
    border-radius: 50%;
    object-fit: cover;
    border: 4px solid #3498db;
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.photo-container {
    text-align: center;
    margin-bottom: 20px;
}

.btn-save {
    background: linear-gradient(135deg, #27ae60 0%, #2ecc71 100%);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-save:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(39, 174, 96, 0.4);
    color: white;
}

.btn-cancel {
    background: linear-gradient(135deg, #95a5a6 0%, #7f8c8d 100%);
    border: none;
    color: white;
    padding: 12px 30px;
    border-radius: 8px;
    font-weight: 600;
    transition: all 0.3s ease;
}

.btn-cancel:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 20px rgba(127, 140, 141, 0.4);
    color: white;
}

.section-title {
    color: #2c3e50;
    font-weight: 700;
    margin-bottom: 20px;
    padding-bottom: 10px;
    border-bottom: 2px solid #3498db;
}

.loader-container {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 60px;
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

.alert-floating {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 9999;
    min-width: 300px;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from { transform: translateX(100%); opacity: 0; }
    to { transform: translateX(0); opacity: 1; }
}
</style>

    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-8">
                <h1><i class="fas fa-user-edit mr-2"></i>Modifier le planteur</h1>
            </div>
            <div class="col-sm-4 text-right">
                <a href="plantations.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-2"></i>Retour à la liste
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid edit-container">
        <!-- Loader -->
        <div id="editLoader" class="loader-container">
            <div class="loader-spinner"></div>
            <p class="mt-3 text-muted">Chargement des informations...</p>
        </div>

        <!-- Error -->
        <div id="editError" class="alert alert-danger" style="display:none;"></div>

        <!-- Form -->
        <form id="editForm" style="display:none;">
            <input type="hidden" id="planteurId" name="id" value="">

            <div class="row">
                <!-- Colonne gauche - Identification -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <i class="fas fa-id-card"></i>Identification
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label>Numéro de fiche</label>
                                <input type="text" class="form-control" id="numero_fiche" name="numero_fiche" readonly>
                            </div>
                            <div class="form-group">
                                <label>Pièce d'identité</label>
                                <input type="text" class="form-control" id="piece_identite" name="piece_identite">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne centrale - Identité -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <i class="fas fa-user"></i>Identité
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label>Nom et prénoms <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nom_prenoms" name="nom_prenoms" required>
                            </div>
                            <div class="form-group">
                                <label>Téléphone</label>
                                <input type="tel" class="form-control" id="telephone" name="telephone">
                            </div>
                            <div class="form-group">
                                <label>Date de naissance</label>
                                <input type="date" class="form-control" id="date_naissance" name="date_naissance">
                            </div>
                            <div class="form-group">
                                <label>Lieu de naissance</label>
                                <input type="text" class="form-control" id="lieu_naissance" name="lieu_naissance">
                            </div>
                            <div class="form-group">
                                <label>Situation matrimoniale</label>
                                <select class="form-control" id="situation_matrimoniale" name="situation_matrimoniale">
                                    <option value="">-- Sélectionner --</option>
                                    <option value="Célibataire">Célibataire</option>
                                    <option value="Marié(e)">Marié(e)</option>
                                    <option value="Divorcé(e)">Divorcé(e)</option>
                                    <option value="Veuf(ve)">Veuf(ve)</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label>Nombre d'enfants</label>
                                <input type="number" class="form-control" id="nombre_enfants" name="nombre_enfants" min="0">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colonne droite - Exploitation -->
                <div class="col-lg-4">
                    <div class="edit-card">
                        <div class="edit-card-header">
                            <i class="fas fa-map-marked-alt"></i>Exploitation
                        </div>
                        <div class="edit-card-body">
                            <div class="form-group">
                                <label>Région</label>
                                <input type="text" class="form-control" id="region" name="region">
                            </div>
                            <div class="form-group">
                                <label>Sous-préfecture / Village</label>
                                <input type="text" class="form-control" id="sous_prefecture_village" name="sous_prefecture_village">
                            </div>
                            <div class="form-group">
                                <label>Latitude</label>
                                <input type="text" class="form-control" id="latitude" name="latitude" placeholder="Ex: 5.345678">
                            </div>
                            <div class="form-group">
                                <label>Longitude</label>
                                <input type="text" class="form-control" id="longitude" name="longitude" placeholder="Ex: -4.012345">
                            </div>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Boutons d'action -->
            <div class="row mt-4 mb-4">
                <div class="col-12 text-center">
                    <button type="button" class="btn btn-cancel mr-3" onclick="window.location.href='plantations.php'">
                        <i class="fas fa-times mr-2"></i>Annuler
                    </button>
                    <button type="submit" class="btn btn-save">
                        <i class="fas fa-save mr-2"></i>Enregistrer les modifications
                    </button>
                </div>
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
