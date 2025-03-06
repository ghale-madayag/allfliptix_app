<script>
import { Link, Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";
import flatPickr from "vue-flatpickr-component";
import "flatpickr/dist/flatpickr.css";

export default {
  components: {
    Layout,
    PageHeader,
    Link, Head,
    flatPickr
  },
};
</script>

<template>
  <Layout>
    <Head title="Inventory" />
    <BRow>
      <BCol xxl="3" sm="6">
        <BCard no-body class="card-animate">
          <BCardBody>
            <div class="d-flex justify-content-between">
              <div>
                <p class="fw-medium text-muted mb-0">Total Sold this month</p>
                <h2 class="mt-4 ff-secondary fw-semibold">
                  <count-to :duration="5000" :startVal="0" :endVal="parseInt(totalSoldThisMonth)"></count-to>
                </h2>
                <!-- <p class="mb-0 text-muted">
                  <BBadge class="bg-light text-success mb-0">
                    <i class="ri-arrow-up-line align-middle"></i> {{ percentageSoldChange }}%
                  </BBadge>
                  vs. previous month
                </p> -->
              </div>
              <div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-success-subtle text-success rounded-circle fs-4">
                    <i class="ri-coupon-3-fill"></i>
                  </span>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xxl="3" sm="6">
        <BCard no-body class="card-animate">
          <BCardBody>
            <div class="d-flex justify-content-between">
              <div>
                <p class="fw-medium text-muted mb-0">Total Profit this Month</p>
                <h2 class="mt-4 ff-secondary fw-semibold">
                  $<count-to :duration="3000" :startVal="0" :endVal="parseFloat(totalQtyThisMonth)" :decimals="2"></count-to>
                </h2>
                <!-- <p class="mb-0 text-muted">
                  <BBadge class="bg-light text-success mb-0">
                    <i class="ri-arrow-up-line align-middle"></i> {{ percentageQtyChange }} %
                  </BBadge>
                  vs. previous month
                </p> -->
              </div>
              <div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-warning-subtle text-warning rounded-circle fs-4">
                    <i class="ri-ticket-2-fill"></i>
                  </span>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol xxl="3" sm="6">
        <BCard no-body class="card-animate">
          <BCardBody>
            <div class="d-flex justify-content-between">
              <div>
                <p class="fw-medium text-muted mb-0">Profit Margin this month</p>
                <h2 class="mt-4 ff-secondary fw-semibold">
                  <count-to :duration="7000" :startVal="0" :endVal="parseFloat(totalProfitMarginThisMonth)" :decimals="2"></count-to>%
                </h2>
                <!-- <p class="mb-0 text-muted">
                  <BBadge class="bg-light text-success mb-0">
                    <i class="ri-arrow-up-line align-middle"></i> {{ percentageProfitMarginChange }}%
                  </BBadge>
                  vs. previous month
                </p> -->
              </div>
              <div>
                <div class="avatar-sm flex-shrink-0">
                  <span class="avatar-title bg-info-subtle text-info rounded-circle fs-4">
                    <i class="ri-money-dollar-circle-line"></i>
                  </span>
                </div>
              </div>
            </div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>

    <BRow>
      <BCol lg="12">
        <BCard no-body id="ticketsList">
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center">
              <h5 class="card-title mb-0 flex-grow-1">Purchase History</h5>
            </div>
          </BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <form>
              <BRow class="g-3">
                <BCol xxl="5" sm="12">
                  <div class="search-box">
                    <input type="text" class="form-control search bg-light border-light"
                      placeholder="Search for ticket details or something..." v-model="searchQuery" />
                    <i class="ri-search-line search-icon"></i>
                  </div>
                </BCol>

                <BCol xxl="3" sm="4">
                    <flat-pickr v-model="filterdate" :config="rangeDateconfig" class="form-control bg-light border-light"
                     placeholder="Select date range" />
                </BCol>
              </BRow>
            </form>
          </BCardBody>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <div class="table-responsive table-card mb-4" id="grid-table"></div>
          </BCardBody>
        </BCard>
      </BCol>
    </BRow>
  </Layout>
</template>

<script setup>
import { defineProps, onMounted, nextTick,ref, watch, computed, watchEffect } from "vue";
import { CountTo } from "vue3-count-to";
import { Grid, h } from "gridjs";
import "gridjs/dist/theme/mermaid.css";
import { router } from '@inertiajs/vue3';

const props = defineProps({
  inventory: Array,
  totalQtyThisMonth: String,
  totalSoldThisMonth: String,
  totalProfitMarginThisMonth: String,
});

const totalQtyThisMonth = props.totalQtyThisMonth;

// Flatpickr date range config
const filterdate = ref(""); // Stores selected date range
const rangeDateconfig = {
    wrap: true, // set wrap to true only when using 'input-group'
    altFormat: "M j, Y",
    altInput: true,
    dateFormat: "Y-m-d",
    mode: "range",
};

const searchQuery = ref("");
let gridInstance = null;


const getFilteredData = () => {
  let filteredData = props.inventory;

  if (filterdate.value) {
    const [startDateStr, endDateStr] = filterdate.value.split(" to ").map(date => date.trim());

    // Convert Flatpickr's date format ("d M, Y") to full timestamp comparison
    const startDate = startDateStr ? new Date(`${startDateStr} 00:00:00`) : null;
    const endDate = endDateStr ? new Date(`${endDateStr} 23:59:59`) : null;

    filteredData = filteredData.filter(event => {
      if (!event.date || event.date === "N/A") return false;

      const eventDate = new Date(event.date);
      return (!startDate || eventDate >= startDate) && (!endDate || eventDate <= endDate);
    });
  }

  // Apply search filter
  if (searchQuery.value) {
    const query = searchQuery.value.toLowerCase();
    filteredData = filteredData.filter(event =>
      event.name.toLowerCase().includes(query) ||
      event.venue.toLowerCase().includes(query) ||
      event.notes?.toLowerCase().includes(query) // Add more fields if needed
    );
  }

  return filteredData;
}


// Function to render Grid.js
const renderGrid = () => {
  if (gridInstance) gridInstance.destroy(); // Destroy previous instance

  gridInstance = new Grid({
    columns: [
      { name: 'Event ID', hidden: true },
      { name: "Event Name"},
      {
        name: "Event Date",
        formatter: (cell) => {
          if (!cell) return "N/A";
          const date = new Date(cell);
          return new Intl.DateTimeFormat("en-US", {
            month: "short",
            day: "2-digit",
            year: "numeric"
          }).format(date);
        }
      },
      "Venue",
      "1 day Avg. Sale",
      "3 days Avg. Sale",
      "7 days Avg. Sale",
      "30 days Avg. Sale",
      "Listing",
      "Sold",
      {
        name: "%Profit Margin",
        formatter: (cell) => {
          if (cell === null || cell === undefined) return "N/A";

          let profitClass = "badge text-bg-secondary bg-light mb-0 text-dark"; // Default (Neutral for 0)
          let iconClass = ""; // No icon for 0

          if (cell > 0) {
            profitClass = "badge bg-success-subtle text-success mb-0";
            iconClass = "ri-arrow-up-line align-middle";
          } else if (cell < 0) {
            profitClass = "badge bg-danger-subtle text-danger mb-0";
            iconClass = "ri-arrow-down-line align-middle";
          }

          return h('span', { className: profitClass }, [
            iconClass ? h('i', { className: iconClass }) : null,
            ` ${cell}`
          ]);
        }
      },
      {
        id: 'actionsColumn',
        name: 'Links',
        align: 'center',
        width: '80px',
        formatter: (cell, row) => {
          const stubhubUrl = row.cells[10]?.data?.trim() || "";
          const vividUrl = row.cells[11]?.data?.trim() || "";
          const event_url = '/inventory/'+row.cells[0]?.data;
          const sold = row.cells[9]?.data;

          const links = [];

          if (stubhubUrl !== "") {
              links.push(h('a', {
                  href: stubhubUrl,
                  className: 'text-success fs-5',
                  target: '_blank',
                  rel: 'noopener noreferrer',
                  title: 'Stubhub'
              }, h('i', { className: 'ri-coupon-2-fill' })));
          }

          if (vividUrl !== "") {
              links.push(h('a', {
                  href: vividUrl,
                  className: 'text-warning fs-5',
                  target: '_blank',
                  rel: 'noopener noreferrer',
                  title: 'Vivid'
              }, h('i', { className: 'ri-coupon-2-fill' })));
          }

          if (sold > 0) {
              links.push(h('a', {
                  href: '#',
                  className: 'text-danger fs-5',
                  title: 'View Sold Inventory',
                  onclick: (e) => {
                      e.preventDefault();
                      router.visit(event_url); // Inertia navigation without reload
                  }
              }, h('i', { className: 'ri-database-2-fill' })));
          }

        return h('div', { className: 'd-flex gap-2 justify-content-left' }, links);
      },
        sort: false
      },
      {name: "updated", hidden: true},
    ],
    data: getFilteredData()
    .sort((a, b) => new Date(b.date) - new Date(a.date))
    .map(event => [
      event.event_id,
      event.name,
      event.date,
      event.venue,
      event.avg_sold_1d,
      event.avg_sold_3d,
      event.avg_sold_7d,
      event.avg_sold_30d,
      event.qty,
      event.sold,
      event.profit_margin,  
      event.stubhub_url?.trim() || "", // Handle empty strings
      event.vivid_url?.trim() || "",                         
    ]),
    pagination: { limit: 100 },
    search: false,
    sort: true,
    theme: 'mermaid',
    resizable: true,
  }).render(document.getElementById("grid-table"));
};

// Watch for changes in Flatpickr input
watch([filterdate, searchQuery], renderGrid);

onMounted(() => {
  renderGrid();
});
</script>

<style>
.gridjs-input {
    padding: 10px 26px!important;
}
</style>