<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../services/api';

const emit = defineEmits(['open-view']);
const stats = ref(null);
const loading = ref(true);
const error = ref('');
const loadDashboard = async () => {
  loading.value = true; error.value = '';
  try { stats.value = (await api.get('/dashboard')).data; }
  catch { error.value = 'Impossible de charger les indicateurs. Vérifiez que Laravel fonctionne sur le port 8000.'; }
  finally { loading.value = false; }
};
const maxSentiment = computed(() => stats.value ? Math.max(...Object.values(stats.value.sentiment_distribution), 1) : 1);
const sentimentRows = computed(() => {
  if (!stats.value) return [];
  const v = stats.value.sentiment_distribution;
  return [
    { key: 'positive', label: 'Positifs', value: v.positive, color: '#10b981' },
    { key: 'neutral', label: 'Neutres', value: v.neutral, color: '#f59e0b' },
    { key: 'negative', label: 'Négatifs', value: v.negative, color: '#ef4444' },
  ];
});
onMounted(loadDashboard);
</script>

<template>
  <section aria-labelledby="dashboard-title">
    <div class="page-intro">
      <div><h2 id="dashboard-title">Vue d’ensemble des avis produits</h2><p>Suivez le volume, la satisfaction et les produits qui concentrent le plus de plaintes.</p></div>
      <button class="secondary-button" type="button" :disabled="loading" @click="loadDashboard">↻ Actualiser</button>
    </div>
    <div v-if="loading" class="metrics-grid" aria-label="Chargement des indicateurs"><div v-for="index in 5" :key="index" class="metric-card skeleton"></div></div>
    <div v-else-if="error" class="error-panel" role="alert"><strong>Tableau de bord indisponible</strong><p>{{ error }}</p><button class="primary-button" type="button" @click="loadDashboard">Réessayer</button></div>
    <template v-else-if="stats">
      <div class="metrics-grid">
        <article class="metric-card"><span class="metric-icon blue" aria-hidden="true">Σ</span><div><p>Total des avis</p><strong>{{ Number(stats.total_reviews).toLocaleString('fr-FR') }}</strong><small>Corpus analysable</small></div></article>
        <article class="metric-card"><span class="metric-icon violet" aria-hidden="true">★</span><div><p>Note moyenne</p><strong>{{ stats.average_rating }}<em>/5</em></strong><small>Qualité perçue</small></div></article>
        <article class="metric-card"><span class="metric-icon green" aria-hidden="true">↑</span><div><p>Avis positifs</p><strong>{{ Number(stats.sentiment_distribution.positive).toLocaleString('fr-FR') }}</strong><small>Satisfaction</small></div></article>
        <article class="metric-card"><span class="metric-icon amber" aria-hidden="true">–</span><div><p>Avis neutres</p><strong>{{ Number(stats.sentiment_distribution.neutral).toLocaleString('fr-FR') }}</strong><small>À examiner</small></div></article>
        <article class="metric-card"><span class="metric-icon red" aria-hidden="true">!</span><div><p>Avis négatifs</p><strong>{{ Number(stats.sentiment_distribution.negative).toLocaleString('fr-FR') }}</strong><small>Priorité métier</small></div></article>
      </div>
      <div class="dashboard-grid">
        <article class="panel sentiment-panel">
          <header class="panel-header"><div><h3>Répartition des sentiments</h3><p>Comparaison des catégories détectées</p></div></header>
          <div class="bar-chart" role="img" aria-label="Répartition des sentiments">
            <div v-for="row in sentimentRows" :key="row.key" class="bar-row"><span>{{ row.label }}</span><div class="bar-track"><div class="bar-fill" :style="{ width: `${(row.value / maxSentiment) * 100}%`, background: row.color }"></div></div><strong>{{ Number(row.value).toLocaleString('fr-FR') }}</strong></div>
          </div>
          <button class="text-button" type="button" @click="emit('open-view', 'reviews')">Explorer tous les avis →</button>
        </article>
        <article class="panel action-panel"><span class="action-symbol" aria-hidden="true">✦</span><h3>Comprendre une plainte</h3><p>Soumettez un nouvel avis au modèle pour identifier la famille de plainte et son niveau de confiance.</p><button class="primary-button" type="button" @click="emit('open-view', 'analyzer')">Lancer une analyse IA</button></article>
      </div>
      <article class="panel products-panel">
        <header class="panel-header"><div><h3>Produits qui reçoivent le plus de plaintes</h3><p>Classement basé sur le nombre d’avis négatifs</p></div><span class="panel-badge">Top {{ stats.top_complaint_products?.length || 0 }}</span></header>
        <div class="table-wrapper"><table><thead><tr><th>Rang</th><th>Marque</th><th>Produit</th><th>Avis négatifs</th></tr></thead><tbody><tr v-for="(product, index) in stats.top_complaint_products" :key="product.product_id"><td><span class="rank">{{ index + 1 }}</span></td><td><strong>{{ product.brand_name }}</strong></td><td>{{ product.product_name }}</td><td><span class="negative-count">{{ product.negative_reviews }}</span></td></tr></tbody></table></div>
      </article>
    </template>
  </section>
</template>

<style scoped>
.metrics-grid{display:grid;grid-template-columns:repeat(5,minmax(170px,1fr));gap:16px;margin-bottom:22px}.metric-card{display:flex;align-items:center;gap:14px;min-height:132px;padding:20px;background:#fff;border:1px solid #dce5f1;border-radius:16px;box-shadow:0 8px 24px rgba(15,42,86,.055)}.metric-card p{margin:0 0 5px;color:#64748b;font-size:.78rem;font-weight:700}.metric-card strong{display:block;color:#14213d;font-size:clamp(1.45rem,2vw,2rem);line-height:1.1}.metric-card strong em{color:#94a3b8;font-size:.85rem;font-style:normal}.metric-card small{color:#94a3b8;font-size:.7rem}.metric-icon{display:grid;place-items:center;flex:0 0 auto;width:44px;height:44px;border-radius:13px;font-size:1.2rem;font-weight:900}.blue{color:#2563eb;background:#dbeafe}.violet{color:#7c3aed;background:#ede9fe}.green{color:#047857;background:#d1fae5}.amber{color:#b45309;background:#fef3c7}.red{color:#b91c1c;background:#fee2e2}.dashboard-grid{display:grid;grid-template-columns:minmax(0,1.65fr) minmax(280px,.7fr);gap:22px;margin-bottom:22px}.panel{padding:24px}.panel-header{display:flex;align-items:flex-start;justify-content:space-between;gap:20px;margin-bottom:24px}.panel-header h3,.action-panel h3{margin:0 0 5px;color:#14213d;font-size:1.05rem}.panel-header p,.action-panel p{margin:0;color:#64748b;font-size:.84rem}.bar-chart{display:grid;gap:18px}.bar-row{display:grid;grid-template-columns:72px minmax(120px,1fr) 70px;align-items:center;gap:13px;font-size:.82rem}.bar-row>span{color:#475569;font-weight:700}.bar-row>strong{text-align:right;color:#14213d}.bar-track{height:10px;overflow:hidden;background:#edf2f7;border-radius:999px}.bar-fill{height:100%;min-width:4px;border-radius:inherit}.text-button{margin-top:25px;padding:0;color:#2563eb;background:transparent;border:0;font-weight:750}.action-panel{position:relative;overflow:hidden;color:white;background:linear-gradient(145deg,#1d4ed8,#173d78);border:0}.action-panel::after{content:'';position:absolute;width:180px;height:180px;right:-70px;bottom:-90px;border:28px solid rgba(255,255,255,.08);border-radius:50%}.action-panel h3,.action-panel p{position:relative;z-index:1;color:white}.action-panel p{margin-bottom:24px;color:#dbeafe;line-height:1.55}.action-symbol{display:grid;place-items:center;width:46px;height:46px;margin-bottom:24px;background:rgba(255,255,255,.14);border-radius:14px;font-size:1.35rem}.action-panel .primary-button{position:relative;z-index:1;color:#173d78;background:white;border-color:white}.panel-badge{padding:6px 10px;color:#1d4ed8;background:#eff6ff;border-radius:999px;font-size:.72rem;font-weight:800}.table-wrapper{overflow-x:auto}table{width:100%;border-collapse:collapse}th,td{padding:14px 12px;border-bottom:1px solid #e6edf5;text-align:left}th{color:#64748b;font-size:.72rem;letter-spacing:.03em;text-transform:uppercase}td{color:#475569;font-size:.84rem}tbody tr:last-child td{border-bottom:0}.rank{display:grid;place-items:center;width:28px;height:28px;color:#1e40af;background:#eff6ff;border-radius:8px;font-weight:800}.negative-count{display:inline-block;min-width:42px;padding:5px 9px;color:#b91c1c;background:#fee2e2;border-radius:999px;text-align:center;font-weight:800}.error-panel{padding:28px;color:#7f1d1d;background:#fff;border:1px solid #fecaca;border-radius:16px}.error-panel p{color:#991b1b}.skeleton{height:132px;background:linear-gradient(90deg,#eaf0f7 25%,#f8fafc 50%,#eaf0f7 75%);background-size:200% 100%;animation:shimmer 1.3s infinite}@keyframes shimmer{to{background-position:-200% 0}}@media(max-width:1250px){.metrics-grid{grid-template-columns:repeat(3,1fr)}}@media(max-width:780px){.metrics-grid{grid-template-columns:repeat(2,1fr)}.dashboard-grid{grid-template-columns:1fr}}@media(max-width:500px){.metrics-grid{grid-template-columns:1fr}.bar-row{grid-template-columns:64px 1fr 58px}.panel{padding:18px}}
</style>
