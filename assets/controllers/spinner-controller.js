import {
   Controller
} from '@hotwired/stimulus';

import { Modal } from 'bootstrap';

export default class extends Controller {
   static targets = ['modal'];
   static values = {
   };

   showSpinner(event) {
      this.openModal(event);
   }

   openModal(event) {
      const modal = new Modal(this.modalTarget);
      modal.show();
  }
}