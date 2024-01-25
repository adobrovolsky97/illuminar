import Vue from 'vue';
import VueRouter from 'vue-router';
import VModal from 'vue-js-modal'
import axios from 'axios'
import VueAxios from 'vue-axios'
import VueTailwind from 'vue-tailwind'
import IlluminarComponent from "./components/IlluminarComponent.vue";
import VueHighlightJS from 'vue-highlightjs'

Vue.use(VueHighlightJS)
Vue.use(VueAxios, axios)
Vue.use(VModal)
Vue.use(VueRouter);
Vue.use(VueTailwind)

Vue.component('illuminar', IlluminarComponent);

new Vue({
    el: '#illuminar',
});
