<script setup>
import { layoutMethods } from "@/state/helpers";
import { Link, router } from '@inertiajs/vue3';
const logout = () => {
  router.post(route('logout'));
};
</script>

<script>
import { Link } from '@inertiajs/vue3';
import simplebar from "simplebar-vue";


/**
 * Nav-bar Component
 */
export default {
  data() {
    return {
      text: null,
      flag: null,
      value: null,
      myVar: 1,
    };
  },
  components: {
    simplebar,
    Link,
  },

  methods: {
    ...layoutMethods,
    toggleHamburgerMenu() {
      var windowSize = document.documentElement.clientWidth;
      let layoutType = document.documentElement.getAttribute("data-layout");

      document.documentElement.setAttribute("data-sidebar-visibility", "show");
      let visiblilityType = document.documentElement.getAttribute("data-sidebar-visibility");

      if (windowSize > 767)
        document.querySelector(".hamburger-icon").classList.toggle("open");

      //For collapse horizontal menu
      if (
        document.documentElement.getAttribute("data-layout") === "horizontal"
      ) {
        document.body.classList.contains("menu") ?
          document.body.classList.remove("menu") :
          document.body.classList.add("menu");
      }

      //For collapse vertical menu

      if (visiblilityType === "show" && (layoutType === "vertical" || layoutType === "semibox")) {
        if (windowSize < 1025 && windowSize > 767) {
          document.body.classList.remove("vertical-sidebar-enable");
          document.documentElement.getAttribute("data-sidebar-size") == "sm" ?
            document.documentElement.setAttribute("data-sidebar-size", "") :
            document.documentElement.setAttribute("data-sidebar-size", "sm");
        } else if (windowSize > 1025) {
          document.body.classList.remove("vertical-sidebar-enable");
          document.documentElement.getAttribute("data-sidebar-size") == "lg" ?
            document.documentElement.setAttribute("data-sidebar-size", "sm") :
            document.documentElement.setAttribute("data-sidebar-size", "lg");
        } else if (windowSize <= 767) {
          document.body.classList.add("vertical-sidebar-enable");
          document.documentElement.setAttribute("data-sidebar-size", "lg");
        }
      }

      //Two column menu
      if (document.documentElement.getAttribute("data-layout") == "twocolumn") {
        document.body.classList.contains("twocolumn-panel") ?
          document.body.classList.remove("twocolumn-panel") :
          document.body.classList.add("twocolumn-panel");
      }
    },
    toggleMenu() {
      this.$parent.toggleMenu();
    },
    toggleRightSidebar() {
      this.$parent.toggleRightSidebar();
    },
    initFullScreen() {
      document.body.classList.toggle("fullscreen-enable");
      if (
        !document.fullscreenElement &&
        /* alternative standard method */
        !document.mozFullScreenElement &&
        !document.webkitFullscreenElement
      ) {
        // current working methods
        if (document.documentElement.requestFullscreen) {
          document.documentElement.requestFullscreen();
        } else if (document.documentElement.mozRequestFullScreen) {
          document.documentElement.mozRequestFullScreen();
        } else if (document.documentElement.webkitRequestFullscreen) {
          document.documentElement.webkitRequestFullscreen(
            Element.ALLOW_KEYBOARD_INPUT
          );
        }
      } else {
        if (document.cancelFullScreen) {
          document.cancelFullScreen();
        } else if (document.mozCancelFullScreen) {
          document.mozCancelFullScreen();
        } else if (document.webkitCancelFullScreen) {
          document.webkitCancelFullScreen();
        }
      }
    },
    toggleDarkMode() {

      if (document.documentElement.getAttribute("data-bs-theme") == "dark") {
        document.documentElement.setAttribute("data-bs-theme", "light");
      } else {
        document.documentElement.setAttribute("data-bs-theme", "dark");
      }

      const mode = document.documentElement.getAttribute("data-bs-theme")
      this.changeMode({
        mode: mode,
      });
    },
  },

  computed: {
    calculateTotalPrice() {
      return this.cartItems.reduce((total, item) => total + parseFloat(item.itemPrice), 0).toFixed(2);
    },
  },
  mounted() {

    document.addEventListener("scroll", function () {
      var pageTopbar = document.getElementById("page-topbar");
      if (pageTopbar) {
        document.body.scrollTop >= 50 || document.documentElement.scrollTop >= 50 ? pageTopbar.classList.add(
          "topbar-shadow") : pageTopbar.classList.remove("topbar-shadow");
      }
    });
    if (document.getElementById("topnav-hamburger-icon"))
      document.getElementById("topnav-hamburger-icon").addEventListener("click", this.toggleHamburgerMenu);
  },
};
</script>

<template>
  <header id="page-topbar">
    <div class="layout-width">
      <div class="navbar-header">
        <div class="d-flex">
          <!-- LOGO -->
          <div class="navbar-brand-box horizontal-logo">
            <Link href="/" class="logo logo-dark">
              <span class="logo-sm">
                <img src="@assets/images/logo-sm.png" alt="" height="50" />
              </span>_page-head.scss
              <span class="logo-lg">
                <img src="@assets/images/logo-dark.png" alt="" height="50" />
              </span>
            </Link>

            <Link href="/" class="logo logo-light">
              <span class="logo-sm">
                <img src="@assets/images/logo-sm.png" alt="" height="50" />
              </span>
              <span class="logo-lg">
                <img src="@assets/images/logo-light.png" alt="" height="50" />
              </span>
            </Link>
          </div>

          <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger"
            id="topnav-hamburger-icon">
            <span class="hamburger-icon">
              <span></span>
              <span></span>
              <span></span>
            </span>
          </button>

        </div>

        <div class="d-flex align-items-center">

          <div class="ms-1 header-item d-none d-sm-flex">
            <BButton type="button" variant="ghost-secondary" class="btn-icon btn-topbar rounded-circle"
              data-toggle="fullscreen" @click="initFullScreen">
              <i class="bx bx-fullscreen fs-22"></i>
            </BButton>
          </div>

          <div class="ms-1 header-item d-none d-sm-flex">
            <BButton type="button" variant="ghost-secondary" class="btn-icon btn-topbar rounded-circle light-dark-mode"
              @click="toggleDarkMode">
              <i class="bx bx-moon fs-22"></i>
            </BButton>
          </div>

          <!-- <BDropdown variant="ghost-dark" dropstart class="ms-1 dropdown"
            :offset="{ alignmentAxis: 57, crossAxis: 0, mainAxis: -42 }"
            toggle-class="btn-icon btn-topbar rounded-circle arrow-none" id="page-header-notifications-dropdown"
            menu-class="dropdown-menu-lg dropdown-menu-end p-0" auto-close="outside">
            <template #button-content>
              <i class='bx bx-bell fs-22'></i>
              <span class="position-absolute topbar-badge fs-10 translate-middle badge rounded-pill bg-danger"><span
                  class="notification-badge">3</span><span class="visually-hidden">unread
                  messages
                </span>
              </span>
            </template>
            <div class="dropdown-head bg-primary bg-pattern rounded-top dropdown-menu-lg">
              <div class="p-3">
                <BRow class="align-items-center">
                  <BCol>
                    <h6 class="m-0 fs-16 fw-semibold text-white">
                      Notifications
                    </h6>
                  </BCol>
                  <BCol cols="auto" class="dropdown-tabs">
                    <BBadge variant="light-subtle" class="bg-light-subtle text-body fs-13"> 4 New</BBadge>
                  </BCol>
                </BRow>
              </div>
            </div>
            <BTabs nav-class="dropdown-tabs nav-tab-custom bg-primary px-2 pt-2">
              <BTab title=" All (4) " class="tab-pane fade py-2 ps-2 show" id="all-noti-tab" role="tabpanel">
                <simplebar data-simplebar style="max-height: 300px" class="pe-2">
                  <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                      <div class="avatar-xs me-3 flex-shrink-0">
                        <span class="avatar-title bg-info-subtle text-info rounded-circle fs-16">
                          <i class="bx bx-badge-check"></i>
                        </span>
                      </div>
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-2 lh-base">
                            Your <b>Elite</b> author Graphic Optimization
                            <span class="text-secondary">reward</span> is
                            ready!
                          </h6>
                        </BLink>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> Just 30 sec ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs flex-shrink-0"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            Angela Bernier
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            Answered to your comment on the cash flow forecast's graph 🔔.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 48 min ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                      <div class="avatar-xs me-3 flex-shrink-0">
                        <span class="avatar-title bg-danger-subtle text-danger rounded-circle fs-16">
                          <i class="bx bx-message-square-dots"></i>
                        </span>
                      </div>
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-2 fs-13 lh-base">
                            You have received <b class="text-success">20</b> new messages in the conversation
                          </h6>
                        </BLink>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 2 hrs
                            ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item position-relative">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-8.jpg" class="me-3 rounded-circle avatar-xs flex-shrink-0"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            Maureen Gibson
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            We talked about a project on linkedin.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 4 hrs
                            ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="my-3 text-center">
                    <BButton type="button" variant="soft-success">
                      View All Notifications
                      <i class="ri-arrow-right-line align-middle"></i>
                    </BButton>
                  </div>
                </simplebar>
              </BTab>

              <BTab title="Messages" class="tab-pane fade py-2 ps-2" id="messages-tab" role="tabpanel"
                aria-labelledby="messages-tab">
                <simplebar data-simplebar style="max-height: 300px" class="pe-2">
                  <div class="text-reset notification-item d-block dropdown-item">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-3.jpg" class="me-3 rounded-circle avatar-xs"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            James Lemire
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            We talked about a project on linkedin.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 30 min ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-2.jpg" class="me-3 rounded-circle avatar-xs"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            Angela Bernier
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            Answered to your comment on the cash flow
                            forecast's graph 🔔.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 2 hrs
                            ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-6.jpg" class="me-3 rounded-circle avatar-xs"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            Kenneth Brown
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            Mentionned you in his comment on 📃 invoice
                            #12501.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 10 hrs
                            ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="text-reset notification-item d-block dropdown-item">
                    <div class="d-flex">
                      <img src="@assets/images/users/avatar-8.jpg" class="me-3 rounded-circle avatar-xs"
                        alt="user-pic" />
                      <div class="flex-grow-1">
                        <BLink href="#!" class="stretched-link">
                          <h6 class="mt-0 mb-1 fs-13 fw-semibold">
                            Maureen Gibson
                          </h6>
                        </BLink>
                        <div class="fs-13 text-muted">
                          <p class="mb-1">
                            We talked about a project on linkedin.
                          </p>
                        </div>
                        <p class="mb-0 fs-11 fw-medium text-uppercase text-muted">
                          <span><i class="mdi mdi-clock-outline"></i> 3 days
                            ago</span>
                        </p>
                      </div>
                      <div class="px-2 fs-15">
                        <input class="form-check-input" type="checkbox" />
                      </div>
                    </div>
                  </div>

                  <div class="my-3 text-center">
                    <BButton type="button" variant="soft-success">
                      View All Messages
                      <i class="ri-arrow-right-line align-middle"></i>
                    </BButton>
                  </div>
                </simplebar>
              </BTab>

              <BTab title="Alerts" class="p-4">
                <simplebar data-simplebar style="max-height: 300px" class="pe-2">
                  <div class="w-25 w-sm-50 pt-3 mx-auto">
                    <img src="@assets/images/svg/bell.svg" class="img-fluid" alt="user-pic" />
                  </div>
                  <div class="text-center pb-5 mt-2">
                    <h6 class="fs-18 fw-semibold lh-base">
                      Hey! You have no any notifications
                    </h6>
                  </div>
                </simplebar>
              </BTab>
            </BTabs>
          </BDropdown> -->

          <BDropdown variant="link" class="ms-sm-3 header-item topbar-user" toggle-class="rounded-circle arrow-none" menu-class="dropdown-menu-end" :offset="{ alignmentAxis: -14, crossAxis: 0, mainAxis: 0 }">
            <template #button-content>
              <span class="d-flex align-items-center">
                <img v-if="$page.props.jetstream.managesProfilePhotos" class="rounded-circle header-profile-user" :src="$page.props.auth.user.profile_photo_url" :alt="$page.props.auth.user.name">
                <span class="text-start ms-xl-2">
                  <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ $page.props.auth.user.name }}</span>
                  <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">{{ $page.props.roles }}</span>
                </span>
              </span>
            </template>
            <h6 class="dropdown-header">Welcome {{ $page.props.auth.user.name }}!</h6>
            <Link class="dropdown-item" :href="route('profile.show')"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle">Profile</span>
            </Link>
            <div class="dropdown-divider"></div>
            <!-- <Link class="dropdown-item" href="#">
            <BBadge variant="success-subtle" class="bg-success-subtle text-success mt-1 float-end">New</BBadge><i class="mdi mdi-cog-outline text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Users</span>
            </Link> -->
            <!-- <Link class="dropdown-item" href="/auth/lockscreen-basic"><i class="mdi mdi-lock text-muted fs-16 align-middle me-1"></i>
            <span class="align-middle"> Lock screen</span>
            </Link> -->

            <!-- Authentication -->
            <form method="POST" @submit.prevent="logout" class="dropdown-item">
              <BButton variant="none" type="submit" class="btn p-0"><i class="mdi mdi-logout text-muted fs-16 align-middle me-1"></i> Logout</BButton>
            </form>
          </BDropdown>
        </div>
      </div>
    </div>
  </header>
</template>