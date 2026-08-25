import { flushPromises, mount } from '@vue/test-utils';
import axe from 'axe-core';
import { beforeEach, describe, expect, it, vi } from 'vitest';

import AiAnalyzer from '../src/components/AiAnalyzer.vue';
import api from '../src/services/api';


vi.mock('../src/services/api', () => ({
  default: {
    post: vi.fn(),
  },
}));


const prediction = {
  prediction_id: 10,
  category: 'device_hardware',
  label: 'Matériel, batterie, écran ou audio',
  decision_score: 0.719638,
  margin: 1.544041,
  threshold: 0.3,
  needs_review: false,
  ranking: [
    {
      category: 'device_hardware',
      label: 'Matériel, batterie, écran ou audio',
      score: 0.719638,
    },
    {
      category: 'software_ecosystem',
      label: 'Logiciel, connexion ou compatibilité',
      score: -0.845995,
    },
  ],
};


function mountAnalyzer() {
  return mount(AiAnalyzer, { attachTo: document.body });
}


describe('AiAnalyzer accessible', () => {
  beforeEach(() => {
    vi.clearAllMocks();
    document.body.innerHTML = '';
  });

  it('associe un libellé et une aide au champ', () => {
    const wrapper = mountAnalyzer();
    const textarea = wrapper.get('#review-text');

    expect(wrapper.get('label').attributes('for')).toBe('review-text');
    expect(textarea.attributes('aria-describedby')).toContain('review-text-help');
    expect(textarea.attributes('required')).toBeDefined();
  });

  it('annonce et focalise une erreur de validation', async () => {
    const wrapper = mountAnalyzer();
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    const alert = wrapper.get('[role="alert"]');
    expect(alert.text()).toContain('au moins 3 caractères');
    expect(wrapper.get('#review-text').attributes('aria-invalid')).toBe('true');
    expect(document.activeElement).toBe(alert.element);
  });

  it('annonce et focalise le résultat', async () => {
    api.post.mockResolvedValue({ data: prediction });
    const wrapper = mountAnalyzer();

    await wrapper.get('#review-text').setValue(
      'The charging port is broken and the battery will not charge.',
    );
    await wrapper.get('form').trigger('submit');
    await flushPromises();

    const region = wrapper.get('[role="region"]');
    expect(api.post).toHaveBeenCalledOnce();
    expect(region.text()).toContain('Matériel, batterie, écran ou audio');
    expect(document.activeElement).toBe(region.element);
  });

  it('ne présente aucune violation automatique axe', async () => {
    const wrapper = mountAnalyzer();
    const audit = await axe.run(wrapper.element);

    expect(audit.violations).toEqual([]);
  });
});
