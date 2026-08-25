<script setup>
import { onMounted, reactive, ref } from 'vue';
import api from '../services/api';

const reviews = ref([]);
const loading = ref(true);
const errorMessage = ref('');
const pagination = reactive({ current: 1, last: 1, total: 0 });
const filters = reactive({ search: '', sentiment: '', note: '', source: '' });

const loadReviews = async (page = 1) => {
  loading.value = true; errorMessage.value = '';
  try {
    const params = { page, per_page: 12 };
    Object.entries(filters).forEach(([key, value]) => { if (value !== '') params[key] = value; });
    const { data } = await api.get('/reviews', { params });
    reviews.value = data.data;
    pagination.current = data.current_page;
    pagination.last = data.last_page;
    pagination.total = data.total;
  } catch {
    reviews.value = [];
    errorMessage.value = "Impossible de récupérer les avis depuis l'API Laravel.";
  } finally { loading.value = false; }
};
const resetFilters = () => { Object.assign(filters, { search: '', sentiment: '', note: '', source: '' }); loadReviews(1); };
const sentimentLabel = (value) => ({ positive: 'Positif', neutral: 'Neutre', negative: 'Négatif' }[value] || value);
onMounted(() => loadReviews());
</script>

<template>
  <section aria-labelledby="reviews-title">
    <div class="page-intro"><div><h2 id="reviews-title">Explorer les avis clients</h2><p>Recherchez dans le corpus et isolez les avis qui nécessitent une analyse métier.</p></div><span class="total-badge">{{ pagination.total.toLocaleString('fr-FR') }} avis</span></div>
    <form class="panel filters" role="search" @submit.prevent="loadReviews(1)">
      <div class="search-field"><label for="review-search">Rechercher</label><input id="review-search" v-model.trim="filters.search" type="search" placeholder="Batterie, écran, livraison…" /></div>
      <div><label for="sentiment-filter">Sentiment</label><select id="sentiment-filter" v-model="filters.sentiment"><option value="">Tous</option><option value="positive">Positif</option><option value="neutral">Neutre</option><option value="negative">Négatif</option></select></div>
      <div><label for="rating-filter">Note</label><select id="rating-filter" v-model="filters.note"><option value="">Toutes</option><option v-for="note in 5" :key="note" :value="note">{{ note }} étoile{{ note > 1 ? 's' : '' }}</option></select></div>
      <div><label for="source-filter">Source</label><input id="source-filter" v-model.trim="filters.source" placeholder="Amazon" /></div>
      <button class="primary-button" type="submit">Filtrer</button>
      <button class="secondary-button" type="button" @click="resetFilters">Réinitialiser</button>
    </form>

    <p class="result-summary" aria-live="polite">{{ loading ? 'Chargement des avis…' : pagination.total.toLocaleString('fr-FR') + ' résultat(s)' }}</p>
    <div v-if="errorMessage" class="error-message" role="alert"><strong>Chargement impossible</strong><p>{{ errorMessage }}</p><button class="primary-button" type="button" @click="loadReviews()">Réessayer</button></div>
    <div v-else-if="loading" class="reviews-grid"><article v-for="i in 6" :key="i" class="review-card loading-card"></article></div>
    <div v-else-if="reviews.length === 0" class="empty-state"><span aria-hidden="true">⌕</span><h3>Aucun avis trouvé</h3><p>Modifiez vos critères de recherche.</p><button class="secondary-button" type="button" @click="resetFilters">Effacer les filtres</button></div>
    <div v-else class="reviews-grid">
      <article v-for="review in reviews" :key="review.id" class="review-card">
        <header><span :class="['sentiment', review.sentiment]">{{ sentimentLabel(review.sentiment) }}</span><span class="rating" :aria-label="review.note + ' étoiles sur 5'">★ {{ review.note }}/5</span></header>
        <blockquote>{{ review.content }}</blockquote>
        <div v-if="review.product" class="product"><span class="product-icon" aria-hidden="true">▣</span><div><small>Produit</small><strong>{{ review.product.name }}</strong><span v-if="review.product.brand">{{ review.product.brand.name }}</span></div></div>
        <footer><span>{{ review.source || 'Source inconnue' }}</span><span v-if="review.language">{{ review.language.toUpperCase() }}</span></footer>
      </article>
    </div>
    <nav v-if="pagination.last > 1 && !loading" class="pagination" aria-label="Pagination des avis">
      <button type="button" :disabled="pagination.current <= 1" @click="loadReviews(pagination.current - 1)">← Précédent</button>
      <span>Page <strong>{{ pagination.current }}</strong> sur {{ pagination.last }}</span>
      <button type="button" :disabled="pagination.current >= pagination.last" @click="loadReviews(pagination.current + 1)">Suivant →</button>
    </nav>
  </section>
</template>

<style scoped>
.total-badge{padding:8px 13px;color:#1e40af;background:#dbeafe;border-radius:999px;font-size:.82rem;font-weight:800;white-space:nowrap}.filters{display:grid;grid-template-columns:minmax(220px,1.8fr) repeat(3,minmax(130px,.7fr)) auto auto;align-items:end;gap:14px;padding:18px;margin-bottom:20px}.filters label{display:block;margin-bottom:6px;color:#475569;font-size:.72rem;font-weight:800}.filters input,.filters select{width:100%;height:42px;padding:0 12px;color:#14213d;background:#fff;border:1px solid #cbd8e8;border-radius:9px}.result-summary{margin:0 0 14px;color:#64748b;font-size:.82rem}.reviews-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:18px}.review-card{display:flex;flex-direction:column;min-height:280px;padding:20px;background:#fff;border:1px solid #dce5f1;border-radius:16px;box-shadow:0 8px 24px rgba(15,42,86,.05);transition:.18s ease}.review-card:hover{transform:translateY(-2px);box-shadow:0 14px 32px rgba(15,42,86,.09)}.review-card header,.review-card footer{display:flex;align-items:center;justify-content:space-between;gap:10px}.sentiment{padding:5px 9px;border-radius:999px;font-size:.68rem;font-weight:850;text-transform:uppercase}.sentiment.positive{color:#047857;background:#d1fae5}.sentiment.neutral{color:#92400e;background:#fef3c7}.sentiment.negative{color:#b91c1c;background:#fee2e2}.rating{color:#a16207;font-size:.78rem;font-weight:800}.review-card blockquote{display:-webkit-box;overflow:hidden;margin:22px 0;color:#334155;font-size:.92rem;line-height:1.65;-webkit-box-orient:vertical;-webkit-line-clamp:5}.product{display:flex;align-items:center;gap:11px;margin-top:auto;padding:12px;background:#f8fafc;border-radius:11px}.product-icon{display:grid;place-items:center;width:34px;height:34px;color:#2563eb;background:#dbeafe;border-radius:9px}.product small,.product strong,.product div>span{display:block}.product small{color:#94a3b8;font-size:.64rem;text-transform:uppercase}.product strong{overflow:hidden;color:#334155;font-size:.75rem;text-overflow:ellipsis;white-space:nowrap}.product div>span{color:#64748b;font-size:.7rem}.review-card footer{margin-top:15px;padding-top:13px;color:#94a3b8;border-top:1px solid #edf2f7;font-size:.68rem;font-weight:700}.pagination{display:flex;align-items:center;justify-content:center;gap:20px;margin-top:26px}.pagination button{padding:9px 13px;color:#1e40af;background:#fff;border:1px solid #cbd8e8;border-radius:9px;font-weight:750}.pagination button:disabled{opacity:.45;cursor:not-allowed}.pagination span{color:#64748b;font-size:.8rem}.empty-state,.error-message{padding:50px 24px;text-align:center;background:#fff;border:1px solid #dce5f1;border-radius:16px}.empty-state>span{font-size:2.4rem}.empty-state h3{margin:12px 0 4px}.empty-state p,.error-message p{color:#64748b}.error-message{color:#991b1b;border-color:#fecaca}.loading-card{min-height:280px;background:linear-gradient(90deg,#eaf0f7 25%,#f8fafc 50%,#eaf0f7 75%);background-size:200% 100%;animation:shimmer 1.3s infinite}@keyframes shimmer{to{background-position:-200% 0}}@media(max-width:1200px){.filters{grid-template-columns:repeat(2,1fr)}.reviews-grid{grid-template-columns:repeat(2,1fr)}}@media(max-width:650px){.filters,.reviews-grid{grid-template-columns:1fr}.pagination{gap:9px}.pagination button{font-size:.75rem}}
</style>
