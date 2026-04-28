<template>
    <div class="card">
        <h5>Float Label</h5>
        <p>FloatLabel is used by wrapping the input and its label.</p>
        <div class="grid p-fluid mt-3">
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <InputText type="text" id="inputtext" v-model="value1" />
                    <label for="inputtext">InputText</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <AutoComplete id="autocomplete" v-model="value2" :suggestions="filteredCountries" @complete="searchCountry($event)" field="name"></AutoComplete>
                    <label for="autocomplete">AutoComplete</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <IconField iconPosition="left">
                        <InputIcon class="pi pi-search" />
                        <InputText type="text" id="lefticon" v-model="value3" />
                    </IconField>
                    <label for="lefticon" style="left: 2.25rem">Left Icon</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <IconField>
                        <InputIcon class="pi pi-spin pi-spinner" />
                        <InputText type="text" id="righticon" v-model="value4" />
                    </IconField>
                    <label for="righticon">Right Icon</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <Calendar inputId="calendar" v-model="value5"></Calendar>
                    <label for="calendar">Calendar</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <Chips inputId="chips" v-model="value6"></Chips>
                    <label for="chips">Chips</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <InputMask id="inputmask" mask="99/99/9999" v-model="value7"></InputMask>
                    <label for="inputmask">InputMask</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <InputNumber id="inputnumber" v-model="value8"></InputNumber>
                    <label for="inputnumber">InputNumber</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <InputGroup>
                    <InputGroupAddon>
                        <i class="pi pi-user"></i>
                    </InputGroupAddon>
                    <FloatLabel>
                        <InputText type="text" id="inputgroup" v-model="value9" />
                        <label for="inputgroup">InputGroup</label>
                    </FloatLabel>
                </InputGroup>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <Dropdown id="dropdown" :options="cities" v-model="value10" optionLabel="name"></Dropdown>
                    <label for="dropdown">Dropdown</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <MultiSelect id="multiselect" :options="cities" v-model="value11" optionLabel="name" :filter="false"></MultiSelect>
                    <label for="multiselect">MultiSelect</label>
                </FloatLabel>
            </div>
            <div class="field col-12 md:col-4">
                <FloatLabel>
                    <Textarea inputId="textarea" rows="3" cols="30" v-model="value12"></Textarea>
                    <label for="textarea">Textarea</label>
                </FloatLabel>
            </div>
        </div>
    </div>
</template>

<script>
import CountryService from '@/service/CountryService';

export default {
    data() {
        return {
            countries: [],
            filteredCountries: null,
            cities: [
                { name: 'New York', code: 'NY' },
                { name: 'Rome', code: 'RM' },
                { name: 'London', code: 'LDN' },
                { name: 'Istanbul', code: 'IST' },
                { name: 'Paris', code: 'PRS' },
            ],
            value1: null,
            value2: null,
            value3: null,
            value4: null,
            value5: null,
            value6: null,
            value7: null,
            value8: null,
            value9: null,
            value10: null,
            value11: null,
            value12: null,
        };
    },
    created() {
        this.countryService = new CountryService();
    },
    mounted() {
        this.countryService.getCountries().then((countries) => {
            this.countries = countries;
        });
    },
    methods: {
        searchCountry(event) {
            // in a real application, make a request to a remote url with the query and
            // return filtered results, for demo we filter at client side
            const filtered = [];
            const query = event.query;
            for (let i = 0; i < this.countries.length; i++) {
                const country = this.countries[i];
                if (country.name.toLowerCase().indexOf(query.toLowerCase()) == 0) {
                    filtered.push(country);
                }
            }
            this.filteredCountries = filtered;
        },
    },
};
</script>

<style lang="scss" scoped>
.floatlabel-demo {
    .field {
        margin-top: 2rem;
    }
}
</style>
