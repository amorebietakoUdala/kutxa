import {
    Controller
} from '@hotwired/stimulus';

//import { useDispatch } from 'stimulus-use';

import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
    static targets = ['file', 'size', 'submitButton'];
    static values = {
        locale: String,
        maxFileSize: String,
        minFileSize: String,
    };

    locale = null;

    async submit(event) {
        event.preventDefault();
        if (this.checkFileSize(event)) {
            this.submitButtonTarget.toggleAttribute('disabled', true);
            this.dispatch('submitting');
            event.currentTarget.submit();
        }
    }

    checkFileSize(event) {
        event.preventDefault();
        const maxBytes = this.calculateSizeInBytes(this.maxFileSizeValue);
        const minBytes = this.calculateSizeInBytes(this.minFileSizeValue);
        const fsize = this.fileTarget.files[0].size;
        this.sizeTarget.innerHTML = this.formatBytes(fsize);
        if (fsize > maxBytes || fsize < minBytes ) {
            Translator.fromJSON(translations);
            Translator.locale = this.localeValue;
            let message = null;
            if ( fsize > maxBytes ) {
                message = Translator.trans('maxFileSizeExceeded', {
                    'fileSize' : this.formatBytes(fsize),
                    'maxFileSize' : this.maxFileSizeValue,
                },'alerts');
            }
            if ( fsize < minBytes ) {
                message = Translator.trans('minFileSizeExceeded', {
                    'fileSize' : this.formatBytes(fsize),
                    'minFileSize' : this.minFileSizeValue,
                },'alerts');
            }
            import ('sweetalert2')
                .then( async (Swal) => {
                    Swal.default.fire({
                        template: '#error',
                        html: message,
                    })
                })
                .catch( error => console.error(error) )
            return false;
        }
        return true;
    }

    formatBytes(bytes, precision = 2) { 
        let units = ['B', 'K', 'M', 'G', 'T']; 
        bytes = Math.max(bytes, 0); 
        let pow = Math.floor((bytes ? Math.log(bytes) : 0) / Math.log(1024)); 
        pow = Math.min(pow, units.length - 1); 
        bytes /= Math.pow(1024, pow);

        return bytes.toFixed(precision) + ' ' + units[pow]; 
    }

    calculateSizeInBytes(maxFileSize) {
        let index = 0;
        let parsed = 0;
        for (let i=0; i < maxFileSize.length ; i++ ) {
            parsed = Number.parseInt(maxFileSize[i]);
            if ( Number.isNaN(parsed) ) {
                index = i;
                break;
            }
        }
        let number = maxFileSize.substring(0,index);
        let unit = maxFileSize.substring(index);
        let units = ['B', 'Ki', 'Mi', 'Gi', 'Ti']; 
        let pow = units.indexOf(unit);
        let bytes = number * Math.pow(1024,pow);

        return bytes;
    }

}