import { startStimulusApp } from '@symfony/stimulus-bundle';

const app = startStimulusApp();
// register any custom, 3rd party controllers here
import SpinController from './controllers/spin_controller.js';
app.register('spin', SpinController);
