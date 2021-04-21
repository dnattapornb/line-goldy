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
                <v-btn small class="ma-2" color="orange" dark @click="editUser(item)">
                    edit
                    <v-icon dark right>mdi-circle-edit-outline</v-icon>
                </v-btn>
            </template>
            <template v-slot:item.data-table-expand="{ expand, isExpanded }">
                <v-icon v-if="isExpanded" @click="expand(!isExpanded)">mdi-chevron-down</v-icon>
                <v-icon v-else @click="expand(!isExpanded)">mdi-chevron-right</v-icon>
            </template>
            <template v-slot:expanded-item="{ headers, item }">
                <td :colspan="headers.length" style="padding: 0px !important;">
                    <v-simple-table>
                        <template v-slot:default>
                            <thead>
                            <tr>
                                <th class="text-right">
                                    Key (Character)*
                                </th>
                                <th class="text-right">
                                    Name
                                </th>
                                <th class="text-right">
                                    Job
                                </th>
                                <th>
                                    <v-btn small class="ma-2" color="primary" dark @click="addCharacter(item.key)">
                                        add
                                        <v-icon dark right>mdi-account-plus</v-icon>
                                    </v-btn>
                                </th>
                            </tr>
                            </thead>
                            <tbody v-if="item.characters.length">
                            <tr v-for="character in item.characters" :key="character.id">
                                <td class="text-right">{{ character.key }}</td>
                                <td class="text-right">{{ character.name }}</td>
                                <td class="text-right" v-if="character.job">{{ character.job.name }}</td>
                                <td class="text-right" v-else>Undefined or invalid job!</td>
                                <td>
                                    <v-btn small
                                           class="ma-2"
                                           color="blue"
                                           dark
                                           @click="editCharacter(item.key, character)">
                                        edit
                                        <v-icon dark right>mdi-account-edit</v-icon>
                                    </v-btn>
                                </td>
                            </tr>
                            </tbody>
                            <tbody v-else>
                            <tr>
                                <td colspan="4" class="text-center">N/A</td>
                            </tr>
                            </tbody>
                        </template>
                    </v-simple-table>
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
                    <!-- User Dialog -->
                    <v-dialog v-model="user.dialog" max-width="600px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn v-show="false" color="primary" dark class="mb-2" v-bind="attrs" v-on="on">
                                New Item
                            </v-btn>
                        </template>
                        <v-card>
                            <v-card-title>
                                <span class="headline">{{ userDialogTitle | uppercase }}</span>
                            </v-card-title>

                            <v-card-text>
                                <v-container>
                                    <v-row>
                                        <v-col cols="12">
                                            <v-switch
                                                    v-model="user.data.published"
                                                    inset
                                                    label="Published"
                                            ></v-switch>
                                        </v-col>
                                        <v-col cols="12">
                                            <v-text-field v-model="user.data.key"
                                                          label="Key (Line)*"
                                                          required
                                                          readonly></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-text-field v-model="user.data.display_name"
                                                          label="Display Name (Line)*"
                                                          required
                                                          :readonly="user.data.is_friend"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-text-field v-model="user.data.name" label="Name"></v-text-field>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" text @click="close">
                                    Cancel
                                </v-btn>
                                <v-btn color="blue darken-1" text @click="save('user')">
                                    Save
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                    <!-- Character Dialog -->
                    <v-dialog v-model="character.dialog" max-width="600px">
                        <template v-slot:activator="{ on, attrs }">
                            <v-btn v-show="false" color="primary" dark class="mb-2" v-bind="attrs" v-on="on">
                                New Item
                            </v-btn>
                        </template>
                        <v-card>
                            <v-card-title>
                                <span class="headline">{{ characterDialogTitle | uppercase }}</span>
                            </v-card-title>

                            <v-card-text>
                                <v-container>
                                    <v-row>
                                        <v-col cols="12">
                                            <v-text-field v-model="character.data.key"
                                                          label="Key (Character)*"
                                                          required
                                                          :readonly="!character.key?false:true"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-text-field v-model="character.data.name"
                                                          label="Name"></v-text-field>
                                        </v-col>
                                        <v-col cols="12" sm="6" md="6">
                                            <v-select
                                                    :hint="`${character.data.job.name}, ${character.data.job.label}`"
                                                    :items="jobs"
                                                    item-text="name"
                                                    item-value="id"
                                                    label="Jobs"
                                                    v-model="character.data.job"
                                                    return-object
                                            ></v-select>
                                        </v-col>
                                    </v-row>
                                </v-container>
                            </v-card-text>

                            <v-card-actions>
                                <v-spacer></v-spacer>
                                <v-btn color="blue darken-1" text @click="close">
                                    Cancel
                                </v-btn>
                                <v-btn color="blue darken-1" text @click="save('character')">
                                    Save
                                </v-btn>
                            </v-card-actions>
                        </v-card>
                    </v-dialog>
                </v-toolbar>
            </template>
        </v-data-table>
        <!--<pre>{{ users }}</pre>-->
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
        jobs: [],
        user: {
            dialog: false,
            key: null,
            data: {
                id: -1,
                key: null,
                name: null,
                display_name: null,
                picture_url: null,
                published: false,
                is_friend: false,
            },
            default: {
                id: -1,
                key: null,
                name: null,
                display_name: null,
                picture_url: null,
                published: false,
                is_friend: false,
            },
        },
        character: {
            dialog: false,
            key: null,
            user: {
                key: null,
            },
            data: {
                id: -1,
                key: null,
                name: null,
                job: {
                    id: 14,
                    label: 'Super Novice',
                    name: 'Novice Guardian',
                },
            },
            default: {
                id: -1,
                key: null,
                name: null,
                job: {
                    id: 14,
                    label: 'Super Novice',
                    name: 'Novice Guardian',
                },
            },
        },
    }),
    computed: {
        userDialogTitle() {
            return this.user.key === null ? 'new user' : 'edit user';
        },
        characterDialogTitle() {
            return this.user.key === null ? 'new character' : 'edit character';
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
            this.getJobs();
            this.getUsers();
        },
        getColor(value) {
            if (value) {
                return 'green';
            }
            else {
                return 'red';
            }
        },
        getJobs() {
            console.log('run "method" : getJobs()');
            this.loading = true;
            axios
            .get('/rom/jobs')
            .then(response => {
                console.log(response);
                this.jobs = response.data;
            })
            .catch(error => {
                console.log(error);
                this.errored = true;
            })
            .finally(() => this.loading = false);
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
        editUser(user) {
            this.user.key = user.key;
            this.user.data = Object.assign({}, user);
            this.user.dialog = true;
        },
        addCharacter(ukey) {
            this.character.key = null;
            this.character.user.key = ukey;
            this.character.data = Object.assign({}, this.character.default);
            this.character.dialog = true;
        },
        editCharacter(ukey, character) {
            this.character.key = character.key;
            this.character.user.key = ukey;
            this.character.data = Object.assign({}, character);
            if (this.character.data.job == null) {
                this.character.data.job = this.character.default.job;
            }
            this.character.dialog = true;
        },
        close() {
            this.user.dialog = false;
            this.character.dialog = false;
            this.$nextTick(() => {
                this.user.data = Object.assign({}, this.user.default);
                this.user.key = null;

                this.character.data = Object.assign({}, this.character.default);
                this.character.key = null;
            });
        },
        save(type) {
            if (type === 'user') {
                if (this.user.key === null) {
                    //
                }
                else {
                    console.log('run "method" : save(\'user\')');
                    this.loading = true;
                    axios
                    .put('/users/' + this.user.data.key, this.user.data)
                    .then(response => {
                        console.log(response);
                        this.getUsers();
                    })
                    .catch(error => {
                        console.log(error);
                        this.errored = true;
                    })
                    .finally(() => this.loading = false);
                }
            }
            else if (type === 'character') {
                if (this.character.key === null) {
                    //
                }
                else {
                    console.log('run "method" : save(\'character\')');
                    this.loading = true;
                    axios
                    .put('/users/' + this.character.user.key + '/characters/' + this.character.data.key, this.character.data)
                    .then(response => {
                        console.log(response);
                        this.getUsers();
                    })
                    .catch(error => {
                        console.log(error);
                        this.errored = true;
                    })
                    .finally(() => this.loading = false);
                }
            }
            this.close();
        },
    },
};
</script>