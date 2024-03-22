require('../bootstrap');

window.Vue = require('vue');

import Vue from 'vue';
import Vuetify from '../plugins/vuetify';

Vue.use(Vuetify);

import VueAxios from 'vue-axios';
import axios from 'axios';

import UserTable from '../components/UserTable.vue';

Vue.config.productionTip = false;

Vue.filter('capitalize', function (value) {
    if (!value) {
        return '';
    }
    value = value.toString();
    return value.charAt(0).toUpperCase() + value.slice(1);
});

Vue.filter('uppercase', function (value) {
    if (!value) {
        return '';
    }
    return value.toString().toUpperCase();
});

new Vue({
    vuetify: Vuetify,
    el: '#app',
    components: {UserTable},
    template: '<UserTable/>',
});