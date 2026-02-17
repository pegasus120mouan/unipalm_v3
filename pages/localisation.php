<?php include('header.php'); ?>

<style>
    #map {
        height: 75vh;
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0,0,0,0.1);
    }
    .map-container {
        padding: 20px;
    }
    .map-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }
    .map-stats {
        display: flex;
        gap: 20px;
    }
    .stat-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 15px 25px;
        border-radius: 10px;
        text-align: center;
    }
    .stat-box h3 {
        margin: 0;
        font-size: 24px;
    }
    .stat-box p {
        margin: 5px 0 0;
        font-size: 12px;
        opacity: 0.9;
    }
    .loader-map {
        display: flex;
        justify-content: center;
        align-items: center;
        height: 75vh;
        background: #f4f6f9;
        border-radius: 10px;
    }
    .loader-map .spinner-border {
        width: 3rem;
        height: 3rem;
    }
    .leaflet-popup-content {
        min-width: 200px;
    }
    .popup-title {
        font-weight: bold;
        font-size: 14px;
        color: #333;
        margin-bottom: 8px;
        border-bottom: 2px solid #667eea;
        padding-bottom: 5px;
    }
    .popup-info {
        font-size: 12px;
        color: #666;
    }
    .popup-info p {
        margin: 4px 0;
    }
    .popup-info i {
        width: 20px;
        color: #667eea;
    }
    .legend {
        background: white;
        padding: 10px 15px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    .legend h4 {
        margin: 0 0 10px;
        font-size: 14px;
    }
    .legend-item {
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 5px 0;
        font-size: 12px;
    }
    .legend-marker {
        width: 12px;
        height: 12px;
        border-radius: 50%;
    }
</style>

<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.4.1/dist/MarkerCluster.Default.css" />

<section class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1><i class="fas fa-map-marked-alt mr-2"></i>Localisation des plantations</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="plantations.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left mr-1"></i>Retour aux plantations
                </a>
            </div>
        </div>
    </div>
</section>

<section class="content">
    <div class="container-fluid">
        <div class="map-container">
            <div class="map-header">
                <div class="map-stats">
                    <div class="stat-box">
                        <h3 id="totalPlantations">-</h3>
                        <p>Plantations</p>
                    </div>
                    <div class="stat-box" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                        <h3 id="totalLocalisees">-</h3>
                        <p>Localisées</p>
                    </div>
                </div>
                <div>
                    <button class="btn btn-primary" id="centerMapBtn">
                        <i class="fas fa-crosshairs mr-1"></i>Centrer la carte
                    </button>
                    <button class="btn btn-success" id="refreshBtn">
                        <i class="fas fa-sync-alt mr-1"></i>Actualiser
                    </button>
                </div>
            </div>

            <div id="loader" class="loader-map">
                <div class="spinner-border text-primary" role="status">
                    <span class="sr-only">Chargement...</span>
                </div>
            </div>

            <div id="map" style="display: none;"></div>

            <div id="errorAlert" class="alert alert-danger mt-3" style="display: none;">
                <i class="fas fa-exclamation-triangle mr-2"></i>
                <span id="errorMessage"></span>
            </div>
        </div>
    </div>
</section>

<?php include('footer.php'); ?>

<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.markercluster@1.4.1/dist/leaflet.markercluster.js"></script>

<script>
(function() {
    const apiUrl = 'https://api.objetombrepegasus.online/api/planteur/actions/planteurs.php';
    
    let map = null;
    let markers = null;
    let allPlantations = [];

    // Initialiser la carte
    function initMap() {
        // Centre par défaut sur la Côte d'Ivoire
        map = L.map('map').setView([6.8276, -5.2893], 8);

        // Couche de tuiles OpenStreetMap
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>',
            maxZoom: 19
        }).addTo(map);

        // Cluster de marqueurs
        markers = L.markerClusterGroup({
            chunkedLoading: true,
            spiderfyOnMaxZoom: true,
            showCoverageOnHover: false,
            maxClusterRadius: 50
        });

        map.addLayer(markers);

        // Ajouter la légende
        addLegend();
    }

    // Ajouter une légende
    function addLegend() {
        const legend = L.control({ position: 'bottomright' });
        legend.onAdd = function() {
            const div = L.DomUtil.create('div', 'legend');
            div.innerHTML = `
                <h4><i class="fas fa-info-circle mr-1"></i>Légende</h4>
                <div class="legend-item">
                    <div class="legend-marker" style="background: #28a745;"></div>
                    <span>Plantation localisée</span>
                </div>
                <div class="legend-item">
                    <div class="legend-marker" style="background: #667eea;"></div>
                    <span>Cluster de plantations</span>
                </div>
            `;
            return div;
        };
        legend.addTo(map);
    }

    // Créer une icône personnalisée
    function createIcon() {
        return L.divIcon({
            html: '<i class="fas fa-map-marker-alt" style="color: #28a745; font-size: 28px; text-shadow: 2px 2px 4px rgba(0,0,0,0.3);"></i>',
            className: 'custom-marker',
            iconSize: [28, 36],
            iconAnchor: [14, 36],
            popupAnchor: [0, -36]
        });
    }

    // Charger les plantations
    async function loadPlantations() {
        document.getElementById('loader').style.display = 'flex';
        document.getElementById('map').style.display = 'none';
        document.getElementById('errorAlert').style.display = 'none';

        try {
            const response = await fetch(apiUrl);
            if (!response.ok) throw new Error('Erreur de connexion à l\'API');

            const result = await response.json();
            
            // Les données sont dans result.data.planteurs
            let plantations = [];
            if (result.data && result.data.planteurs && Array.isArray(result.data.planteurs)) {
                plantations = result.data.planteurs;
            } else if (result.data && Array.isArray(result.data)) {
                plantations = result.data;
            } else if (Array.isArray(result)) {
                plantations = result;
            }

            allPlantations = plantations;

            // Filtrer les plantations avec coordonnées valides (dans exploitation.latitude/longitude)
            const localisees = plantations.filter(p => {
                const exp = p.exploitation;
                return exp && exp.latitude && exp.longitude && 
                    !isNaN(parseFloat(exp.latitude)) && 
                    !isNaN(parseFloat(exp.longitude));
            });

            // Mettre à jour les stats
            document.getElementById('totalPlantations').textContent = plantations.length;
            document.getElementById('totalLocalisees').textContent = localisees.length;

            // Afficher la carte
            document.getElementById('loader').style.display = 'none';
            document.getElementById('map').style.display = 'block';

            if (!map) {
                initMap();
            }

            // Effacer les anciens marqueurs
            markers.clearLayers();

            // Ajouter les marqueurs
            const bounds = [];
            localisees.forEach(plantation => {
                const exp = plantation.exploitation;
                const lat = parseFloat(exp.latitude);
                const lng = parseFloat(exp.longitude);

                const marker = L.marker([lat, lng], { icon: createIcon() });

                // Calculer la superficie totale des cultures
                let superficieTotale = 0;
                if (plantation.cultures && Array.isArray(plantation.cultures)) {
                    plantation.cultures.forEach(c => {
                        if (c.superficie_ha) superficieTotale += parseFloat(c.superficie_ha);
                    });
                }

                // Popup avec infos
                const popupContent = `
                    <div class="popup-title">
                        <i class="fas fa-seedling mr-1"></i>${plantation.nom_prenoms || 'Plantation'}
                    </div>
                    <div class="popup-info">
                        <p><i class="fas fa-user"></i> ${plantation.nom_prenoms || 'N/A'}</p>
                        <p><i class="fas fa-phone"></i> ${plantation.telephone || 'N/A'}</p>
                        <p><i class="fas fa-map-pin"></i> ${exp.region || ''} ${exp.sous_prefecture_village || ''}</p>
                        <p><i class="fas fa-ruler-combined"></i> ${superficieTotale > 0 ? superficieTotale.toFixed(2) + ' ha' : 'N/A'}</p>
                        <p><i class="fas fa-globe"></i> ${lat.toFixed(6)}, ${lng.toFixed(6)}</p>
                    </div>
                `;

                marker.bindPopup(popupContent);
                markers.addLayer(marker);
                bounds.push([lat, lng]);
            });

            // Ajuster la vue sur tous les marqueurs
            if (bounds.length > 0) {
                map.fitBounds(bounds, { padding: [50, 50] });
            }

        } catch (err) {
            document.getElementById('loader').style.display = 'none';
            document.getElementById('errorAlert').style.display = 'block';
            document.getElementById('errorMessage').textContent = err.message;
        }
    }

    // Centrer la carte
    document.getElementById('centerMapBtn').addEventListener('click', function() {
        if (map && markers.getLayers().length > 0) {
            map.fitBounds(markers.getBounds(), { padding: [50, 50] });
        }
    });

    // Actualiser
    document.getElementById('refreshBtn').addEventListener('click', loadPlantations);

    // Charger au démarrage
    loadPlantations();
})();
</script>
