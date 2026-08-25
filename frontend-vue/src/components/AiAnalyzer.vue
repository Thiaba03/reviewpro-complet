<script setup>
import { nextTick, ref } from 'vue';
import api from '../services/api';

const text = ref('');
const result = ref(null);
const errorMessage = ref('');
const isAnalyzing = ref(false);
const errorElement = ref(null);
const resultElement = ref(null);

const focusElement = async (element) => {
    await nextTick();
    element.value?.focus();
};

const analyzeReview = async () => {
    errorMessage.value = '';
    result.value = null;

    if (text.value.trim().length < 3) {
        errorMessage.value = 'Saisissez un avis d’au moins 3 caractères.';
        await focusElement(errorElement);
        return;
    }

    isAnalyzing.value = true;

    try {
        const response = await api.post('/ai/predict', {
            text: text.value.trim(),
        });

        result.value = response.data;
        await focusElement(resultElement);
    } catch (error) {
        errorMessage.value =
            error.response?.data?.message
            || 'Impossible de contacter le service d’analyse.';
        await focusElement(errorElement);
    } finally {
        isAnalyzing.value = false;
    }
};
</script>

<template>
  <section
    class="analyzer"
    aria-labelledby="ai-analyzer-title"
    :aria-busy="isAnalyzing"
  >
    <h2 id="ai-analyzer-title">Tester l’analyse IA</h2>

    <p id="ai-analyzer-description" class="description">
      Saisissez un avis pour identifier sa principale famille de plainte.
    </p>

    <form novalidate @submit.prevent="analyzeReview">
      <label for="review-text">Avis à analyser</label>

      <p id="review-text-help" class="field-help">
        Le texte doit contenir entre 3 et 5 000 caractères.
      </p>

      <textarea
        id="review-text"
        v-model="text"
        name="review_text"
        rows="4"
        minlength="3"
        maxlength="5000"
        required
        aria-describedby="review-text-help review-character-count"
        :aria-invalid="errorMessage ? 'true' : 'false'"
        placeholder="Exemple : The charging port is broken and the battery will not charge."
      ></textarea>

      <p id="review-character-count" class="character-count">
        {{ text.length }} / 5 000 caractères
      </p>

      <button
        type="submit"
        :disabled="isAnalyzing"
      >
        {{ isAnalyzing ? 'Analyse en cours…' : 'Analyser cet avis' }}
      </button>
    </form>

    <p class="sr-only" role="status" aria-live="polite">
      {{ isAnalyzing ? 'Analyse de l’avis en cours.' : '' }}
    </p>

    <p
      v-if="errorMessage"
      ref="errorElement"
      class="error"
      role="alert"
      tabindex="-1"
    >
      {{ errorMessage }}
    </p>

    <div
      v-if="result"
      ref="resultElement"
      :class="[
        'result',
        result.needs_review ? 'uncertain' : 'reliable'
      ]"
      role="region"
      aria-labelledby="prediction-title"
      aria-live="polite"
      tabindex="-1"
    >
      <h3 id="prediction-title">{{ result.label }}</h3>

      <p>
        Catégorie technique :
        <code>{{ result.category }}</code>
      </p>

      <p>
        Marge de décision :
        {{ Number(result.margin).toFixed(3) }}
      </p>

      <p class="status">
        <span aria-hidden="true">
          {{ result.needs_review ? '⚠️' : '✅' }}
        </span>
        {{
          result.needs_review
            ? 'Vérification humaine nécessaire'
            : 'Prédiction exploitable'
        }}
      </p>

      <details>
        <summary>Voir le classement complet</summary>

        <ol>
          <li
            v-for="item in result.ranking"
            :key="item.category"
          >
            {{ item.label }} :
            {{ Number(item.score).toFixed(3) }}
          </li>
        </ol>
      </details>
    </div>
  </section>
</template>

<style scoped>
.analyzer {
    max-width: 900px;
    margin: 40px auto 0;
    padding: 28px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #dbe3ee;
    border-radius: 16px;
    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.10);
}

h2 {
    margin: 0 0 8px;
    color: #0f172a;
    font-size: 1.7rem;
}

.description {
    margin-bottom: 18px;
    color: #475569;
}

label {
    display: block;
    margin-bottom: 6px;
    font-weight: 700;
}

.field-help,
.character-count {
    color: #475569;
    font-size: 0.925rem;
}

.field-help {
    margin: 0 0 8px;
}

.character-count {
    margin: 6px 0 0;
    text-align: right;
}

textarea {
    box-sizing: border-box;
    width: 100%;
    padding: 14px;
    color: #0f172a;
    background: #ffffff;
    border: 1px solid #64748b;
    border-radius: 10px;
    font: inherit;
    line-height: 1.5;
    resize: vertical;
}

textarea:focus-visible,
button:focus-visible,
summary:focus-visible,
.error:focus,
.result:focus {
    outline: 3px solid #1d4ed8;
    outline-offset: 3px;
}

textarea[aria-invalid="true"] {
    border-color: #b91c1c;
}

button {
    display: block;
    margin: 16px auto 0;
    padding: 12px 22px;
    color: #ffffff;
    background: #1d4ed8;
    border: 0;
    border-radius: 9px;
    font: inherit;
    font-weight: 700;
    cursor: pointer;
}

button:hover:not(:disabled) {
    background: #1e40af;
}

button:disabled {
    color: #ffffff;
    background: #64748b;
    cursor: wait;
}

.error {
    margin-top: 16px;
    padding: 12px;
    color: #7f1d1d;
    background: #fee2e2;
    border: 1px solid #b91c1c;
    border-radius: 8px;
}

.result {
    margin-top: 24px;
    padding: 22px;
    color: #0f172a;
    border: 1px solid;
    border-left-width: 6px;
    border-radius: 12px;
}

.result.reliable {
    background: #d1fae5;
    border-color: #047857;
}

.result.uncertain {
    background: #ffedd5;
    border-color: #c2410c;
}

.result h3 {
    margin: 0 0 14px;
    color: #0f172a;
    font-size: 1.35rem;
}

.result p,
.result summary,
.result li {
    color: #1e293b;
}

.result code {
    padding: 3px 7px;
    color: #1e3a8a;
    background: rgba(255, 255, 255, 0.85);
    border-radius: 5px;
}

.status {
    font-weight: 800;
}

details {
    margin-top: 16px;
    padding-top: 12px;
    border-top: 1px solid rgba(15, 23, 42, 0.25);
}

summary {
    padding: 4px;
    font-weight: 700;
    cursor: pointer;
}

li {
    margin: 8px 0;
}

.sr-only {
    position: absolute;
    width: 1px;
    height: 1px;
    padding: 0;
    margin: -1px;
    overflow: hidden;
    clip: rect(0, 0, 0, 0);
    white-space: nowrap;
    border: 0;
}
</style>
