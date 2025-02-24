<script>
import { Link, Head } from '@inertiajs/vue3';
import Layout from "@/Layouts/main.vue";
import PageHeader from "@/Components/page-header.vue";

export default {
  components: {
    Layout,
    PageHeader,
    Link, Head
  },
};
</script>

<template>
  <Layout>
    <Head title="Dashboard" />
    <BRow>
      <BCol lg="12" class="mb-4">
        <div class="d-flex align-items-lg-center flex-lg-row flex-column">
              <div class="flex-grow-1">
                  <h4 class="fs-16 mb-1">{{ greeting }}, {{ auth.user.name }}</h4>
                  <p class="text-muted mb-0">Here's what's happening with your system today.</p>
              </div>
          </div><!-- end card header -->
      </BCol>
    </BRow>
    <BRow>
      <BCol lg="8">
        <BCard>
          <BCardHeader class="border-0">
            <div class="d-flex align-items-center">
              <h5 class="card-title mb-0 flex-grow-1">% Profit Margin</h5>
            </div>
          </BCardHeader>
          <BCardBody class="border border-dashed border-end-0 border-start-0">
            <apexchart class="apex-charts" type="area" :options="options" :series="series"></apexchart>
          </BCardBody>
        </BCard>
      </BCol>
      <BCol lg="4">
        <BRow>
          <BCard no-body>
            <BCardHeader class="border-0">
              <BCardTitle class="mb-0 flex-grow-1">This Month Sales Forecast</BCardTitle>
            </BCardHeader>
            <BCardBody class="border border-dashed border-end-0 border-start-0">
              <apexchart class="apex-charts" type="bar" :options="optionsSales" :series="seriesSales"></apexchart>
            </BCardBody>
          </BCard>
        </BRow>
       <BRow>
        <BCard no-body>
          <BCardHeader class="border-0">
            <BCardTitle class="mb-0 flex-grow-1">Top Events This Year</BCardTitle>
          </BCardHeader>
            <BCardBody class="border border-dashed border-end-0 border-start-0">
              <ul class="list-group list-group-flush border-dashed">
                  <li class="list-group-item ps-0" v-for="(item, index) of topSoldTickets" :key="index">
                      <BRow class="align-items-center g-3">
                          <div class="col-auto">
                              <div class="avatar-sm p-1 py-2 h-auto bg-secondary-subtle rounded-3">
                                  <div class="text-center">
                                      <h5 class="mb-0">{{ item.date }}</h5>
                                      <div class="text-muted">{{ item.month }}</div>
                                  </div>
                              </div>
                          </div>
                          <BCol>
                              <h5 class="text-muted mt-0 mb-1 fs-13">{{ item.time }}</h5>
                              <BLink href="#" class="text-reset fs-14 mb-0">{{ item.name }}</BLink>
                          </BCol>
                          <BCol sm="auto">
                            <h5 class="text-reset mt-0 mb-1 fs-13">{{ item.total_sold }}</h5>
                            <BLink href="#" class="text-muted fs-14 mb-0">Sold</BLink>
                          </BCol>
                      </BRow>
                  </li>
              </ul>
          </BCardBody>
        </BCard>
      </BRow>
      </BCol>
    </BRow>
  </Layout>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import getChartColorsArray from "@/common/getChartColorsArray";

let props = defineProps({
    auth: Object,
    profitThisYear: Object,
    profitLastYear: Object,
    qtyThisMonth: Object,
    soldThisMonth: Object,
    topSoldTickets: Object,
})

const getGreeting = () => {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
        return 'Good morning!';
    } else if (hour >= 12 && hour < 18) {
        return 'Good afternoon!';
    } else {
        return 'Good evening!';
    }
};

const greeting = computed(() => getGreeting());

const options = ref({
  chart: {
      height: 500,
      type: "line",
      zoom: {
        enabled: false,
      },
      toolbar: {
        show: false,
      },
    },
    colors: getChartColorsArray('["--vz-success", "--vz-secondary"]'),
    dataLabels: {
      enabled: false,
    },
    stroke: {
      dashArray: [3, 3],
      width: [1, 1],
      curve: "straight",
    },
    xaxis: {
        categories: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    },
    yaxis: {
        labels: {
            formatter: function (value) {
                return value + '% ' ; // Add peso sign before the value
            }
        }
    },
    fill: {
        opacity: [0.25, 0.25,],
    },
    zoom: {
        enabled: false
    },
    toolbar: {
        show: false
    },
    grid: {
      row: {
        colors: ["transparent", "transparent"], // takes an array which will be repeated on columns
        opacity: 0.2,
      },
      borderColor: "#f1f1f1",
    },
    markers: {
      style: "inverted",
      size: 6,
    },
    legend: {
      position: "top",
      horizontalAlign: "right",
      floating: true,
      offsetY: 0,
      offsetX: -5,
    },
    responsive: [
      {
        breakpoint: 600,
        options: {
          chart: {
            toolbar: {
              show: false,
            },
          },
          legend: {
            show: false,
          },
        },
      },
    ],
  
});

const optionsSales = ref({
  chart: {
      type: "bar",
      height: 410,
      toolbar: {
        show: false,
      },
    },
    plotOptions: {
      bar: {
        horizontal: true,
        dataLabels: {
          position: "top",
        },
      },
    },
    dataLabels: {
      enabled: true,
      offsetX: -6,
      style: {
        fontSize: "12px",
        colors: ["#fff"],
      },
    },
    stroke: {
      show: true,
      width: 1,
      colors: ["#fff"],
    },
    tooltip: {
      shared: true,
      intersect: false,
    },
    xaxis: {
      categories: ["Tickets"],
    },
    fill:{
      opacity: [0.7,0.7],
    },
    colors: getChartColorsArray('["--vz-warning","--vz-success"]'),
  
});

const currentYear = new Date().getFullYear();
const lastYear = currentYear - 1;

const series = ref([
    {
        name: currentYear,
        type: 'area',
        data: props.profitThisYear,
    },
    {
        name: lastYear,
        type: 'area',
        data: props.profitLastYear
    }
  ]
);

const seriesSales = ref([
    {
        name: "Quantity",
        type: 'bar',
        data: props.qtyThisMonth
    },
    {
        name: "Sold",
        type: 'bar',
        data: props.soldThisMonth
    }
  ]
);
</script>