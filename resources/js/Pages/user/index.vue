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
      <Head title="Users" />
        <BRow>
            <BCol lg="12">
                <BCard no-body id="ticketsList">
                    <BCardHeader class="border-0">
                        <BRow class="g-4 align-items-center">
                            <BCol class="col-sm">
                            <div>
                                <h5 class="card-title mb-0 flex-grow-1">Users List</h5>
                            </div>
                        </BCol>
                        <BCol class="col-sm-auto">
                            <div class="d-flex flex-wrap align-items-start gap-2">
                                <button v-if="selectedRows?.length > 0" class="btn btn-soft-danger" @click="deleteSelectedRows"><i class="ri-delete-bin-2-line"></i></button>
                                <button type="button" class="btn btn-secondary add-btn" @click="openModal"><i class="ri-add-line align-bottom me-1"></i> Add New</button>
                            </div>
                        </BCol>
                        </BRow>
                    </BCardHeader>
                    <BCardBody class="border border-dashed border-end-0 border-start-0">
                        <div class="table-responsive table-card mb-4" id="grid-table"></div>
                    </BCardBody>
                </BCard>
            </BCol>
        </BRow>
        <div class="modal fade" v-if="showModal" :class="{ 'show': showModal }" tabindex="-1" role="dialog">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-light p-3">
                        <h5 class="modal-title"></h5>
                        <button type="button" class="btn-close" aria-label="Close" @click="closeModal"></button>
                    </div>
                    <form class="tablelist-form" autocomplete="off"  @submit.prevent="submitHandler">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input v-model="form.name" id="name" type="text" class="form-control" :class="{ 'is-invalid': form.errors.name }" placeholder="Enter Full Name" required />
                                <div class="invalid-feedback">Please enter first name.</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input v-model="form.email" id="email" type="email" class="form-control" :class="{ 'is-invalid': form.errors.email }" placeholder="Enter email" />
                                <div class="invalid-feedback">{{ form.errors.email  }}</div>
                            </div>

                            <div>
                                <label for="role">Select Role</label>
                                <select class="form-select mb-3" v-model="form.roles" aria-label="Default select example">
                                    <option selected disabled>Select Role</option>
                                    <option value="User">User</option>
                                    <option value="Administrator">Administrator</option>
                                </select>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <div class="hstack gap-2 justify-content-end">
                                <button type="button" class="btn btn-light" @click="closeModal">Close</button>
                                <div v-if="form.processing" class="spinner-border text-success" role="status">
                                    <span class="sr-only">Loading...</span>
                                </div>
                                <button type="submit" class="btn btn-success is-loading">{{ submitButtonText }}</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </Layout>
</template>

<script setup>
    import { useForm } from "@inertiajs/vue3";
    import { ref, onMounted, computed } from 'vue';
    import { Grid, h } from "gridjs";
    import "gridjs/dist/theme/mermaid.css";
    import Swal from 'sweetalert2/dist/sweetalert2';
    import 'sweetalert2/dist/sweetalert2.min.css';

    let gridInstance = null;
    const selectedRows = ref([]);

    let props = defineProps({
        users: Object,
    });

    let form = useForm({
        id: null,
        name:null,
        name: null,
        email:null,
        roles: null,
    });
    
    const showModal = ref(false);

    //Modal
    const editingMode = ref(false);

    const openModal = () => {
        showModal.value = true;
        editingMode.value = false;
    };

    const closeModal = () => {
        showModal.value = false;
        form.reset();
    };

    const formatUserData = users => {
        return users.map(user => [
            user.id,
            user.name,
            user.email,
            formatCreatedAt(user.created_at),
            user.roles,
            user.is_verified
        ]);
    };

    const renderGrid = () => {
        if (gridInstance) gridInstance.destroy(); 
        gridInstance = new Grid({
            columns: [
                {            
                    id: 'checkboxCol',
                    width: '40px',
                    name: h('input', { type: 'checkbox', className: 'form-check-input', onChange: event => selectAllRows(event) }),
                    align: 'center',
                    formatter: (cell, row) => {
                        return h('input', {
                            type: 'checkbox',
                            className: 'form-check-input',
                            onClick: event => handleCheckboxClick(event, row)
                        });
                    },
                    sort: false, // Disable sorting on this column
                    hidden: true,
                },
                "Name",
                "Email",
                "Date Created",
                {   
                    id: 'roleColumn',
                    name:'Role',
                    align: 'center',
                    formatter: (cell, row) =>{
                        const statusText = cell;
                        let badge;

                        if (cell == 'Administrator') {
                            badge = 'bg-danger';
                        } else if (cell == 'User') {
                            badge = 'bg-info';
                        } else {
                            badge = 'bg-success';
                        }

                        return h('span', { className: 'badge ' + badge, onClick: () => editModal(row) }, [
                            statusText
                        ])
                    }
                },
                {
                    id: 'verifColumn',
                    name: 'Verified',
                    align: 'center',
                    formatter: (cell, row) => {
                        const isVerified = cell === true;
                        return h('i', { 
                            className: (isVerified ? 'ri-shield-check-fill text-success' : 'ri-close-circle-fill text-danger') + ' d-block text-center',
                            onClick: () => editModal(row) 
                        });
                    }
                },
                // {
                // id: 'actionsColumn',
                // name: 'Actions',
                // align: 'center',
                // width: '75px',
                // formatter: (cell, row) => {
                //     const status = row.cells[4].data;
                //     const roles = row.cells[5].data;
                //     return h('div', { className: 'd-flex justify-content-center' }, [
                //         h('ul', { className: 'list-inline hstack gap-2 mb-0' }, [
                //             h('li', { 
                //                 className: 'list-inline-item', 
                //                 'data-bs-toggle': 'tooltip', 
                //                 'data-bs-trigger': 'hover', 
                //                 'data-bs-placement': 'top', 
                //                 title: 'Edit' 
                //             }, [
                //                 h('a', { href: 'javascript:void(0);', className: 'text-muted d-inline-block', onClick: () => editModal(row) }, [
                //                     h('i', { className: 'ri-pencil-fill fs-16' })
                //                 ])
                //             ])
                //         ])
                //     ]);
                // }
                // }
            ],
            data: props.users
            .map(event => [
                event.id,
                event.name,    
                event.email,
                formatCreatedAt(event.created_at),
                event.roles[0],
                event.is_verified         
            ]),
            pagination: { limit: 10 },
            search: false,
            sort: false,
            theme: 'mermaid',
        }).render(document.getElementById("grid-table"));
        };
    onMounted(() => {
        renderGrid();
    });

    function formatCreatedAt(dateString) {
        const date = new Date(dateString);
        const options = { 
            month: 'long', 
            day: 'numeric', 
            year: 'numeric', 
            hour: 'numeric', 
            minute: 'numeric',
            hour12: true
        };
        return date.toLocaleDateString('en-US', options);
    }

    const submitButtonText = computed(() => {
        return editingMode.value ? 'Edit User' : 'Add User';
    });

    const submitHandler = () => {
        if (editingMode.value) {
            edit(); // Call edit method if in editing mode
        } else {
            publish(); // Call publish method if not in editing mode
        }
    };

    const publish = () =>{

        form.post('/user/store',{
            onStart: () => {},
            onSuccess: () => {
                
                showModal.value = false;
                renderGrid();

                form.reset();
            },
        });
    }


</script>

<style>
.modal{
    background-color: rgba(0, 0, 0, 0.5);
}

</style>