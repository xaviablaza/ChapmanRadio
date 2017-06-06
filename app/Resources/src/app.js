import './vendor'
import App from './App.vue'

import Dashboard from './page/dashboard/Dashboard.vue'

// -------------------------------DASHBOARD----------------------------------------------
import AllCommentDashboard from './page/dashboard/comment/AllCommentDashboard.vue'

import AllPostDashboard from './page/dashboard/post/AllPostDashboard.vue'
import AddNewPostDashboard from './page/dashboard/post/AddNewPostDashboard.vue'
import PostCategoryDashboard from './page/dashboard/post/PostCategoryDashboard.vue'
import PostTagDashboard from './page/dashboard/post/PostTagDashboard.vue'

import ProfileDashboard from './page/dashboard/profile/ProfileDashboard.vue'
import ProfileSettingsDashboard from './page/dashboard/profile/ProfileSettingDashboard.vue'

import SettingDashboard from './page/dashboard/setting/SettingDashboard.vue'

import AllShowDashboard from './page/dashboard/show/AllShowDashboard.vue'
import AddNewShowDashboard from './page/dashboard/show/AddNewShowDashboard.vue'
import ShowCategoryDashboard from './page/dashboard/show/ShowCategoryDashboard.vue'
import ShowTagDashboard from './page/dashboard/show/ShowTagDashboard.vue'
import ShowDashboard from './page/dashboard/show/ShowDashboard.vue'

import AddNewUserDashboard from './page/dashboard/user/AddNewUserDashboard.vue'
import AllUserDashboard from './page/dashboard/user/AllUserDashboard.vue'
import UserDashboard from './page/dashboard/user/UserDashboard.vue'
// -------------------------------App----------------------------------------------

import AppPage from './page/app/AppPage.vue'
import LoginPage from './page/app/LoginPage.vue'
import RegisterPage from './page/app/RegisterPage.vue'
import PostPage from './page/app/PostPage.vue'
import PostSinglePage from './page/app/PostSinglePage.vue'
import ShowSinglePage from './page/app/ShowSinglePage.vue'
import ContactPage from './page/app/ContactPage.vue'
import EventPage from './page/app/EventPage.vue'
import SchedulePage from './page/app/SchedulePage.vue'
import ShowPage from './page/app/ShowPage.vue'
import HomePage from './page/app/HomePage.vue'

import Vue from 'vue'
import VueRouter from 'vue-router'

const router = new VueRouter({ routes: [
  {
    path: '/', component: AppPage,
    children: [
        // Posts --------------------------------------------------------------------
        {name: 'post', path: 'post', component: PostPage},
        {name: 'post_single', path: 'post/:token/:slug', component: PostSinglePage},
        // Shows --------------------------------------------------------------------
        {name: 'show', path: 'show', component: ShowPage},
        {name: 'show_single', path: 'show/:token/:slug', component: ShowSinglePage},
        // --------------------------------------------------------------------------
        {name: 'contact', path: 'contact', component: ContactPage},
        {name: 'event', path: 'event', component: EventPage},
        {name: 'schedule', path: 'schedule', component: SchedulePage},
        {name: 'home', path: '', component: HomePage},
        {name: 'login', path: 'login', component: LoginPage},
        {name: 'register', path: 'register', component: RegisterPage}
    ]
  },
  // -------------------------------DASHBOARD----------------------------------------------
  {
    name: 'dashboard', path: '/dashboard/', component: Dashboard,
    children: [
        // shows
        {name: 'dashboard_all_show', path: 'show', component: AllShowDashboard},
        {name: 'dashboard_add_new_show', path: 'show/add', component: AddNewShowDashboard},
        {name: 'dashboard_category_show', path: 'show/category', component: ShowCategoryDashboard},
        {name: 'dashboard_tag_show', path: 'show/tag', component: ShowTagDashboard},
        {name: 'dashboard_show', path: 'show/:id', component: ShowDashboard},

        // settings
        {name: 'dashboard_setting', path: 'setting', component: SettingDashboard},

        // user
        {name: 'dashboard_add_new_user', path: 'user/new-user', component: AddNewUserDashboard},
        {name: 'dashboard_all_user', path: 'user', component: AllUserDashboard},
        {name: 'dashboard_user', path: 'user/:id', component: UserDashboard},

        // profile
        {name: 'dashboard_profile', path: 'profile', component: ProfileDashboard},
        {name: 'dashboard_profile_setting', path: 'profile/setting', component: ProfileSettingsDashboard},

        // post
        {name: 'dashboard_all_post', path: 'post', component: AllPostDashboard},
        {name: 'dashboard_add_new_post', path: 'post/add', component: AddNewPostDashboard},
        {name: 'dashboard_category_post', path: 'post/category', component: PostCategoryDashboard},
        {name: 'dashboard_tag_post', path: 'post/tag', component: PostTagDashboard},

        // comment
        {name: 'dashboard_all_comment', path: 'comment', component: AllCommentDashboard}
    ]
  }
  // -------------------------------App----------------------------------------------
]})

window.onload = function () {
    // router.push({name : "summary"})
  new Vue({
    router,
    render: h => h(App)
  }).$mount('#app')
}
