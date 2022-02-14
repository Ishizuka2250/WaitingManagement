import Vue  from "vue";
import VueRouter from "vue-router";
import Login from "./views/Login"

Vue.use(VueRouter);

const router = new VueRouter(
  {
    mode: 'history',
    base: process.env.BASE_URL,
    routes: [
      {
        path: '/app/login',
        name: 'login',
        component: Login
      }
    ]
  }
);

export default router;
