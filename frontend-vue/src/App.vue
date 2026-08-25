<script setup>
import { computed, ref } from 'vue';
import Dashboard from './components/Dashboard.vue';
import Reviews from './components/Reviews.vue';
import AiAnalyzer from './components/AiAnalyzer.vue';
import HealthMonitor from './components/HealthMonitor.vue';

const activeView = ref('dashboard');
const menuOpen = ref(false);
const navigation = [
  { id: 'dashboard', label: 'Tableau de bord', icon: '▦' },
  { id: 'reviews', label: 'Avis clients', icon: '◫' },
  { id: 'analyzer', label: 'Analyse IA', icon: '✦' },
  { id: 'monitoring', label: 'Monitorage', icon: '◉' },
];
const current = computed(() => navigation.find((item) => item.id === activeView.value));
const selectView = (view) => {
  activeView.value = view;
  menuOpen.value = false;
};
</script>

<template>
  <div class="app-shell">
    <a class="skip-link" href="#main-content">Aller au contenu principal</a>

    <aside :class="['sidebar', { open: menuOpen }]" aria-label="Navigation principale">
      <div class="brand">
        <span class="brand-mark" aria-hidden="true">R</span>
        <div><strong>ReviewPro</strong><small>Opinion Intelligence</small></div>
      </div>

      <nav>
        <button
          v-for="item in navigation"
          :key="item.id"
          type="button"
          :class="['nav-item', { active: activeView === item.id }]"
          :aria-current="activeView === item.id ? 'page' : undefined"
          @click="selectView(item.id)"
        >
          <span class="nav-icon" aria-hidden="true">{{ item.icon }}</span>
          {{ item.label }}
        </button>
      </nav>

      <div class="sidebar-footer">
        <span class="status-dot" aria-hidden="true"></span>
        <div><strong>Environnement local</strong><small>Laravel · FastAPI · Vue</small></div>
      </div>
    </aside>

    <div v-if="menuOpen" class="backdrop" aria-hidden="true" @click="menuOpen = false"></div>

    <div class="workspace">
      <header class="topbar">
        <button class="menu-button" type="button" :aria-expanded="menuOpen" aria-label="Ouvrir le menu" @click="menuOpen = !menuOpen">☰</button>
        <div>
          <p class="eyebrow">ReviewPro / {{ current?.label }}</p>
          <h1>{{ current?.label }}</h1>
        </div>
        <div class="topbar-meta"><span class="live-dot" aria-hidden="true"></span>Prototype opérationnel</div>
      </header>

      <main id="main-content" class="main-content" tabindex="-1">
        <Dashboard v-if="activeView === 'dashboard'" @open-view="selectView" />
        <Reviews v-else-if="activeView === 'reviews'" />
        <AiAnalyzer v-else-if="activeView === 'analyzer'" />
        <HealthMonitor v-else-if="activeView === 'monitoring'" />
      </main>
    </div>
  </div>
</template>
