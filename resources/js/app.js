import './bootstrap';

import Alpine from 'alpinejs';
import { initMatrixRain } from './matrix-rain';

window.Alpine = Alpine;
window.initMatrixRain = initMatrixRain;

Alpine.start();

document.addEventListener('DOMContentLoaded', () => {
    initMatrixRain('matrix-rain');
});
