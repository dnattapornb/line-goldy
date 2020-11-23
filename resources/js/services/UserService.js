window._ = require('lodash');

try {
    window.Popper = require('popper.js').default;
    window.$ = window.jQuery = require('jquery');

    require('bootstrap');
} catch (e) {}

window.axios = require('axios');

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

import Vue from 'vue';
import UserComponent from '../components/UserComponent';

Vue.config.productionTip = false;

import VueMaterial from 'vue-material';
// import 'vue-material/dist/vue-material.min.css';
// import 'vue-material/dist/theme/default.css';

Vue.use(VueMaterial);

new Vue({
    el: '#app',
    components: {UserComponent},
    template: '<UserComponent/>',
});
