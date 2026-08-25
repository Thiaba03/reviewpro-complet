<script setup>
import { ref } from 'vue';
import api from '../services/api';

const content = ref('');
const isSubmitting = ref(false);

const submitReview = async () => {
    if (content.value.length < 5) return alert("Le message est trop court !");
    
    isSubmitting.value = true;
    try {
        // On envoie l'avis au backend
        await api.post('/reviews', { content: content.value });
        
        // On vide le champ
        content.value = '';
        
        // ASTUCE SIMPLE : On recharge la page pour voir le nouvel avis et les stats à jour
        window.location.reload(); 
    } catch (error) {
        console.error("Erreur:", error);
        alert("Erreur lors de l'envoi.");
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
  <div class="form-card">
    <h3>✍️ Ajouter un avis</h3>
    <textarea 
      v-model="content" 
      placeholder="Votre expérience (ex: Super produit, livraison rapide...)" 
      rows="3"
    ></textarea>
    <button @click="submitReview" :disabled="isSubmitting">
      {{ isSubmitting ? 'Analyse en cours...' : 'Envoyer' }}
    </button>
  </div>
</template>

<style scoped>
.form-card {
    background: white;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
}
h3 { margin-top: 0; color: #334155; }
textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #cbd5e1;
    border-radius: 8px;
    margin-bottom: 10px;
    font-family: inherit;
    resize: vertical;
}
button {
    background-color: #3b82f6;
    color: white;
    border: none;
    padding: 10px 20px;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.2s;
}
button:hover { background-color: #2563eb; }
button:disabled { background-color: #94a3b8; cursor: not-allowed; }
</style>