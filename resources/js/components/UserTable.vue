<template>
    <v-app>
        <v-data-table
                :headers="headers"
                :items="users"
                :search="search"
                :options.sync="options"
                :loading="loading"
                sort-by="index"
                item-key="id"
                :expanded.sync="expanded"
                show-expand
                :single-expand="singleExpand"
                fixed-header
                height="100%"
                :items-per-page="15"
                class="elevation-1">
            <template v-slot:item.display_name="{ item }">
                <div :inner-html.prop="item.display_name | highlight(search)"></div>
            </template>
            <template v-slot:item.name="{ item }">
                <div :inner-html.prop="item.name | highlight(search)"></div>
            </template>
            <template v-slot:item.status="{ item }">
                <v-chip
                        :color="getColor(item.published)"
                        dark
                >
                    <span v-if="item.published">Published</span>
                    <span v-else>Published</span>
                </v-chip>

                <v-chip
                        :color="getColor(item.is_friend)"
                        dark
                >
                    <span v-if="item.is_friend">
                        <v-icon dark>mdi-account-multiple-check-outline</v-icon>
                    </span>
                    <span v-else>
                        <v-icon dark>mdi-account-multiple-remove-outline</v-icon>
                    </span>
                </v-chip>
            </template>
            <template v-slot:item.action="{ item }">
                <v-btn small class="ma-2" color="orange" dark @click="editItem(item)">
                    edit
                    <v-icon dark right>mdi-circle-edit-outline</v-icon>
                </v-btn>
            </template>
            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length">
                    <div class="row sp-details">
                        <div class="col-4 text-right">
                            <v-text-field
                                    label="Label"
                            ></v-text-field>
                        </div>
                        <div class="col-4 text-right">
                            <v-text-field
                                    label="Label 1"
                            ></v-text-field>
                        </div>
                        <div class="col-4 text-right">
                            <v-text-field
                                    label="Label 2"
                            ></v-text-field>
                        </div>
                    </div>
                </td>
            </template>

            <template v-slot:top>
                <v-toolbar flat>
                    <v-toolbar-title>{{ 'user' | uppercase }}</v-toolbar-title>
                    <v-divider class="mx-4" inset vertical></v-divider>
                    <v-switch
                            v-model="singleExpand"
                            label="Single expand"
                            class="ma-2"
                    ></v-switch>
                    <v-spacer></v-spacer>

                    <v-text-field
                            v-model="search"
                            append-icon="mdi-magnify"
                            label="Search"
                            single-line
                            hide-details
                    ></v-text-field>
                    <v-btn class="ma-2" color="primary" dark @click="getUsers">
                        reload
                        <v-icon dark right>mdi-restart</v-icon>
                    </v-btn>
                    <v-dialog v-model="dialog" max-width="600px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn v-show="false" color="primary" dark class="mb-2" v-bind="attrs" v-on="on">
                                New Item
                            </v-btn>
                        </template>
                        <v-card>
                            <v-card-title>
                                <span class="headline">{{ formTitle | uppercase }}</span>
                            </v-card-title>

                            <v-card-text>
                                <v-container>
                                    <v-row>
                                        <v-col cols="12">
                                            <v-switch
                                                    v-model="editedItem.published"
                                                    inset
                                                    label="Published"
                                            ></v-switch>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field v-model="editedItem.key"
                                                          label="Key (Line)*"
                                                          required
                                                          readonly></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-text-field v-model="editedItem.display_name"
                                                          label="Display Name (Line)*"
                                                          required
                                                          :readonly="editedItem.is_friend"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-text-field v-model="editedItem.name" label="Name"></v-text-field>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" text @click="close">
                                    Cancel
                                </v-btn>
                                <v-btn color="blue darken-1" text @click="save">
                                    Save
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-toolbar>
            </template>
        </v-data-table>
    </v-app>
</template>

<script>
export default {
    name: 'UserTable',
    filters: {
        highlight: function (value, query) {
            return value.replace(new RegExp(query, 'ig'), '<span class=\'yellow\'>' + query + '</span>');
        },
        highlightRow: function (row, query) {
            let ret = '';
            Object.keys(row).forEach((column) => {
                if (typeof row[column] === 'string') {
                    ret = ret + row[column].replace(new RegExp(query, 'ig'), '<span class=\'blue\'>' + query + '</span>');
                }
            });
            return ret;
        },
    },
    data: () => ({
        dialog: false,
        expanded: [],
        singleExpand: true,
        search: null,
        loading: false,
        options: {},
        headers: [
            {text: '', value: 'data-table-expand'},
            {
                text: 'Id',
                align: 'center',
                value: 'id',
            },
            {
                text: 'Key (Line)*',
                sortable: false,
                value: 'key',
            },
            {
                text: 'Display Name (Line)*',
                align: 'start',
                sortable: false,
                value: 'display_name',
            },
            {
                text: 'Name',
                value: 'name',
            },
            {
                text: 'Status(es)',
                align: 'center',
                sortable: false,
                value: 'status',
            },
            {
                text: 'Action(s)',
                align: 'center',
                sortable: false,
                value: 'action',
            },
        ],
        users: [],
        editedIndex: -1,
        editedItem: {
            id: '',
            key: '',
            name: '',
            display_name: null,
            picture_url: null,
            published: false,
            is_friend: false,
        },
        defaultItem: {
            id: '',
            key: '',
            name: null,
            display_name: null,
            picture_url: null,
            published: false,
            is_friend: false,
        },
    }),
    computed: {
        formTitle() {
            return this.editedIndex === -1 ? 'new user' : 'edit user';
        },
    },
    watch: {
        dialog(val) {
            val || this.close();
        },
    },
    created() {
        console.log('Component created.');
        this.initialize();
    },
    mounted() {
        console.log('Component mounted.');
    },
    methods: {
        initialize() {
            this.getUsers();
        },
        getUsers() {
            console.log('run "method" : getUsers()');
            this.loading = true;
            axios
            .get('/users')
            .then(response => {
                console.log(response);
                this.users = response.data;
            })
            .catch(error => {
                console.log(error);
                this.errored = true;
            })
            .finally(() => this.loading = false);
        },
        updateUsers(user) {
            console.log('run "method" : updateUsers()');
            this.loading = true;
            axios
            .put('/users/' + user.id, user)
            .then(response => {
                console.log(response);
                this.getUsers();
            })
            .catch(error => {
                console.log(error);
                this.errored = true;
            })
            .finally(() => this.loading = false);
        },
        getColor(value) {
            if (value) {
                return 'green';
            }
            else {
                return 'red';
            }
        },
        editItem(item) {
            this.editedIndex = this.users.indexOf(item);
            this.editedItem = Object.assign({}, item);
            this.dialog = true;
        },
        close() {
            this.dialog = false;
            this.$nextTick(() => {
                this.editedItem = Object.assign({}, this.defaultItem);
                this.editedIndex = -1;
            });
        },
        save() {
            if (this.editedIndex > -1) {
                this.updateUsers(this.editedItem);
            }
            else {
                // new object
                this.users.push(this.editedItem);
            }
            this.close();
        },
    },
};
</script>