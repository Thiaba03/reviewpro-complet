<script setup>
import { computed, onMounted, ref } from 'vue';
import api from '../services/api';

const health = ref(null);
const loading = ref(true);
const error = ref('');
const checkedAt = computed(() => health.value?.checked_at ? new Date(health.value.checked_at).toLocaleString('fr-FR') : '—');

const loadHealth = async () => {
  loading.value = true; error.value = '';
  try { health.value = (await api.get('/health')).data; }
  catch (exception) {
    if (exception.response?.data?.checks) health.value = exception.response.data;
    else error.value = 'Le contrôle de santé applicatif ne répond pas.';
  } finally { loading.value = false; }
};
const statusLabel = (status) => ({ ok: 'Opérationnel', degraded: 'Dégradé', unavailable: 'Indisponible' }[status] || status);
onMounted(loadHealth);
</script>

<template>
  <section aria-labelledby="monitoring-title">
    <div class="page-intro"><div><h2 id="monitoring-title">Santé de l’application</h2><p>Contrôle consolidé de Laravel, de la base de données et du service d’intelligence artificielle.</p></div><button class="secondary-button" type="button" :disabled="loading" @click="loadHealth">↻ Relancer le contrôle</button></div>
    <div v-if="loading" class="monitor-loading">Contrôle des services en cours…</div>
    <div v-else-if="error" class="monitor-error" role="alert"><strong>Monitorage indisponible</strong><p>{{ error }}</p><button class="primary-button" type="button" @click="loadHealth">Réessayer</button></div>
    <template v-else-if="health">
      <article :class="['health-summary', health.status]">
        <span class="health-symbol" aria-hidden="true">{{ health.status === 'ok' ? '✓' : '!' }}</span>
        <div><p>État global</p><h3>{{ statusLabel(health.status) }}</h3><small>Dernière vérification : {{ checkedAt }}</small></div>
        <div class="latency"><span>{{ Number(health.latency_ms).toFixed(2) }}</span><small>ms de contrôle</small></div>
      </article>
      <div class="service-grid">
        <article v-for="(check, key) in health.checks" :key="key" class="service-card panel">
          <header><div :class="['service-dot', check.status]"></div><span :class="['service-status', check.status]">{{ statusLabel(check.status) }}</span></header>
          <span class="service-icon" aria-hidden="true">{{ key === 'application' ? 'L' : key === 'database' ? 'DB' : 'AI' }}</span>
          <h3>{{ key === 'application' ? 'Backend Laravel' : key === 'database' ? 'Base de données' : 'Service FastAPI' }}</h3>
          <dl>
            <template v-if="check.service"><dt>Service</dt><dd>{{ check.service }}</dd></template>
            <template v-if="check.connection"><dt>Connexion</dt><dd>{{ check.connection }}</dd></template>
            <template v-if="check.model"><dt>Modèle</dt><dd>{{ check.model }}</dd></template>
            <template v-if="check.model_version"><dt>Version</dt><dd>{{ new Date(check.model_version).toLocaleDateString('fr-FR') }}</dd></template>
          </dl>
        </article>
      </div>
      <article class="panel explanation">
        <div><span aria-hidden="true">i</span></div>
        <section><h3>Comment interpréter cet écran ?</h3><p>Un état HTTP 200 signifie que les trois composants nécessaires à la prédiction sont disponibles. Si FastAPI s’arrête, Laravel reste accessible mais le statut devient dégradé et l’endpoint retourne HTTP 503.</p></section>
      </article>
    </template>
  </section>
</template>

<style scoped>
.monitor-loading,.monitor-error{padding:42px;color:#64748b;text-align:center;background:#fff;border:1px solid #dce5f1;border-radius:16px}.monitor-error{color:#991b1b;border-color:#fecaca}.health-summary{display:flex;align-items:center;gap:18px;margin-bottom:22px;padding:24px;color:#065f46;background:linear-gradient(135deg,#ecfdf5,#d1fae5);border:1px solid #a7f3d0;border-radius:17px}.health-summary.degraded{color:#92400e;background:linear-gradient(135deg,#fffbeb,#fef3c7);border-color:#fde68a}.health-symbol{display:grid;place-items:center;width:54px;height:54px;color:#fff;background:#059669;border-radius:16px;font-size:1.7rem;font-weight:900}.degraded .health-symbol{background:#d97706}.health-summary p,.health-summary h3,.health-summary small{margin:0}.health-summary p{font-size:.72rem;font-weight:800;text-transform:uppercase}.health-summary h3{font-size:1.5rem}.latency{margin-left:auto;text-align:right}.latency span,.latency small{display:block}.latency span{font-size:1.6rem;font-weight:900}.service-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:20px;margin-bottom:22px}.service-card{padding:22px}.service-card header{display:flex;align-items:center;justify-content:space-between}.service-dot{width:10px;height:10px;background:#ef4444;border-radius:50%}.service-dot.ok{background:#10b981}.service-dot.degraded{background:#f59e0b}.service-status{padding:5px 8px;color:#991b1b;background:#fee2e2;border-radius:999px;font-size:.65rem;font-weight:800;text-transform:uppercase}.service-status.ok{color:#047857;background:#d1fae5}.service-status.degraded{color:#92400e;background:#fef3c7}.service-icon{display:grid;place-items:center;width:48px;height:48px;margin:24px 0 14px;color:#1d4ed8;background:#dbeafe;border-radius:14px;font-weight:900}.service-card h3{margin:0 0 18px;font-size:1rem}.service-card dl{display:grid;grid-template-columns:80px 1fr;gap:8px;margin:0;padding-top:14px;border-top:1px solid #edf2f7;font-size:.75rem}.service-card dt{color:#94a3b8}.service-card dd{overflow:hidden;margin:0;color:#475569;font-weight:700;text-overflow:ellipsis;white-space:nowrap}.explanation{display:flex;gap:16px;padding:22px}.explanation>div{display:grid;place-items:center;flex:0 0 auto;width:38px;height:38px;color:#1d4ed8;background:#dbeafe;border-radius:50%;font-weight:900}.explanation h3{margin:0 0 6px;font-size:.95rem}.explanation p{margin:0;color:#64748b;font-size:.82rem;line-height:1.6}@media(max-width:850px){.service-grid{grid-template-columns:1fr}.latency{display:none}}@media(max-width:520px){.health-summary{align-items:flex-start}.health-symbol{width:44px;height:44px}}
</style>
