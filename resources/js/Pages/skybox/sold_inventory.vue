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
        <Head title="Sold Inventory" />
        <div class="profile-foreground position-relative mx-n4 mt-n4">
      <div class="profile-wid-bg">
        <img src="@assets/images/login-bg.webp" alt="" class="profile-wid-img" />
      </div>
    </div>
    <div class="pt-4 mb-4 mb-lg-3 pb-lg-4 profile-wrapper">
      <BRow class="g-12">
        <!-- <BCol cols="auto">
          <div class="avatar-lg">
            <img src="@assets/images/users/avatar-1.jpg" alt="user-img" class="img-thumbnail rounded-circle" />
          </div>
        </BCol> -->
        <BCol>
          <div class="p-2">
            <h3 class="text-white mb-1">{{ event_details.name }}</h3>
            <p class="text-white text-opacity-75">{{ event_details.date }}</p>
            <div class="hstack text-white-50 gap-1">
              <div>
                <i class="ri-building-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>{{ event_details.venue }}
              </div>
              <div class="me-2">
                <i class="ri-map-pin-user-line me-1 text-white text-opacity-75 fs-16 align-middle"></i>{{ event_details.city }}, {{ event_details.state }}, {{ event_details.country }}
              </div>
            </div>
          </div>
        </BCol>
        <BCol cols="12" lg="auto" order-lg="0" class="order-last">
          <BRow class="text text-white-50 text-center">
            <div class="d-flex profile-wrapper">
                <div class="d-flex justify-content-end">
                  <a target="_blank" :href='"https://skybox.vividseats.com/inventory/sold?eventId="+event_details.id' class="btn btn-success"><i
                      class="ri-links-line align-bottom"></i> Skybox Vividseats</a>
                </div>
              </div>
          </BRow>
        </BCol>
      </BRow>
    </div>
        <BRow>
          <BCol xl="12">
            <Widget :crmWidgets="crmWidgets"/>
          </BCol>
        </BRow>
        <BRow>
            <BCol lg="12">
                <BCard no-body id="ticketsList">
                    <BCardHeader class="border-0 rounded-0">
                        <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Sold Inventory</h5>
                        </div>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <div class="table-responsive table-card mb-4" id="grid-table"></div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
    </Layout>
</template>

<script setup>
import { defineProps, onMounted, ref, watch, nextTick } from "vue";
import { Grid } from "gridjs";
import "gridjs/dist/theme/mermaid.css";
import Widget from "./widget.vue";

const props = defineProps({
  sold_inventory: Object,
  event_details: Object,
  inventory:Object,
});

const crmWidgets = [
    {
      id: 1,
      label: "Quantity",
      badge: "",
      icon: "ri-coupon-3-line",
      counter: props.inventory.qty,
      decimals: 0,
      suffix: "",
      prefix: "",
    },
    {
      id: 2,
      label: "Total",
      badge: "",
      icon: "ri-exchange-dollar-line",
      counter: parseFloat(props.inventory.total),
      decimals: 2,
      suffix: "",
      prefix: "$",
    },
    {
      id: 3,
      label: "Profit",
      badge: "",
      icon: "ri-money-dollar-box-line",
      counter: parseFloat(props.inventory.total_profit),
      decimals: 2,
      suffix: "",
      prefix: "$",
    },
    {
      id: 4,
      label: "Profit Margin",
      badge: "",
      icon: "ri-pulse-line",
      counter: parseFloat(props.inventory.profit),
      decimals: 2,
      suffix: "%",
      prefix: "",
    },
    {
      id: 5,
      label: "Return of Investment",
      badge: "",
      icon: "ri-money-dollar-circle-line",
      counter: props.inventory.roi,
      decimals: 2,
      prefix: "",
      separator: ".",
      suffix: "%",
    },
  ];

const gridInstance = ref(null);

const renderGrid = () => {
  if (gridInstance.value) gridInstance.value.destroy();

  nextTick(() => {
    const rows = Array.isArray(props.sold_inventory?.rows) ? props.sold_inventory.rows : [];
    const soldInventoryTotal = props.sold_inventory?.soldInventoryTotals || {}; // Ensure it's accessible

    gridInstance.value = new Grid({
      columns: [
        { name: 'Invoice ID', hidden: false },
        {
            name: "Invoice Date",
            width:"180px",
            formatter: (cell) => {
            if (!cell) return "N/A";
            const date = new Date(cell);
            return new Intl.DateTimeFormat("en-US", {
                month: "short",
                day: "2-digit",
                year: "numeric",
                hour: "2-digit",
                minute: "2-digit",
                hour12: true,
            }).format(date);
            }
        },
        "Qty",
        "In-Hand Date",
        "Section",
        "Row",
        "Seats",
        "Unit Cost",
        "Total Cost",
        "Total",
        "Unit Ticket Sales",
        "Profit",
        "%Profi Margin",
        "%ROI",
      ],
      data: rows.map(event => [
        event.invoiceId,
        event.invoiceDate,
        event.quantity,
        event.inHandDate,
        event.section,
        event.row,
        event.lowSeat+"-"+ event.highSeat,
        '$'+event.unitCostAverage,
        '$'+event.cost,
        '$'+event.total,
        '$'+event.unitTicketSales,
        '$'+event.profit,
        event.profitMargin || 0,
        soldInventoryTotal.totalROI || 0,
        event.roi,        
      ]),
      pagination: { limit: 100 },
      search: false,
      sort: true,
      theme: 'mermaid',
      resizable: true,
    }).render(document.getElementById("grid-table"));
  });
};

onMounted(() => {
  renderGrid();
});

// Watch for prop changes and re-render the grid
watch(() => props.sold_inventory, () => {
  renderGrid();
}, { deep: true });

</script>