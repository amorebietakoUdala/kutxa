import {
    Controller
} from '@hotwired/stimulus';

import Translator from 'bazinga-translator';
const translations = require('../../public/translations/' + Translator.locale + '.json');

export default class extends Controller {
    static targets = ['file', 'size', 'submitButton'];
    static values = {
        locale: String,
        maxFileSize: String,
    };

    locale = null;

    submit(event) {
        event.preventDefault();
        console.log(event);
        if (this.checkMaxFileSize(event)) {
            this.submitButtonTarget.toggleAttribute('disabled', false);
            event.currentTarget.submit();
        }
    }

    checkMaxFileSize(event) {
        event.preventDefault();
        const maxBytes = this.calculateMaxSizeInBytes(this.maxFileSizeValue);
        const fsize = this.fileTarget.files[0].size;
        this.sizeTarget.innerHTML = this.formatBytes(fsize);
        if (fsize > maxBytes) {
            import ('sweetalert2')
                .then( async (Swal) => {
                    Translator.fromJSON(translations);
                    Translator.locale = this.localeValue;
                    Swal.default.fire({
                        template: '#error',
                        html: Translator.trans('maxFileSizeExceeded', {
                            'fileSize' : this.formatBytes(fsize),
                            'maxFileSize' : this.maxFileSizeValue,
                        },'alerts'),
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

        // Uncomment one of the following alternatives:
        //$bytes /= pow(1024, $pow);
        bytes /= (1 << (10 * pow)); 
        return bytes.toFixed(precision) + ' ' + units[pow]; 
    }

    calculateMaxSizeInBytes(maxFileSize) {
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
        let units = ['B', 'K', 'M', 'G', 'T']; 

        let pow = units.indexOf(unit);
        let bytes = number * Math.pow(1024,pow);

        return bytes;
    }

}