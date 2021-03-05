<template>
  <div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div class="flex flex-row justify-between px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="w-auto align-left">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            Activate Site Type - {{ siteType.name }}
          </h3>
          <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">

          </p>
        </div>

        <div class="w-auto flex-shrink-0 flex text-right">
          <ListAction route="site_types.admin.index" />
        </div>
      </div>

      <div class="flex flex-col px-4 lg:px-8 py-5 text-gray-500">
        <div class="pb-4 border-b border-gray-100">
          <div class="bg-blue-100 p-5 w-full sm:w-1/2 border-l-4 border-blue-500 my-6 mx-auto">
            <div class="flex space-x-3 text-blue-700">
              <i class="fas fa-info-circle mr-3 mt-2" />

              <div class="flex-1 leading-tight">
                Please note that by installing this site type, there is a risk of losing data currently on your website. We review every site type manually to ensure a high quality, but still be careful when activating a site type.
              </div>
            </div>
          </div>

          <component
            v-bind:is="siteType.package"
            :vendor="siteType.vendor"
            :packageName="siteType.package"
          />
        </div>

        <form v-if="isLoaded" @submit.prevent="submit" class="py-5 text-gray-500 mt-4">
          <h3 class="text-xl leading-6 font-medium text-gray-900 mb-8">
            Basic Config
          </h3>

          <div class="flex flex-wrap -mx-3 mb-4 pb-4 border-b border-gray-100">
            <template v-for="field in basicConfig">
              <div class="w-full md:w-1/2 px-3 flex-col mb-4">
                <label :for="`form-${field.column_name}`" class="text-lg font-normal text-gray-700 w-auto">
                  {{ field.name }}
                  <span
                    v-if="field.is_required_edit"
                    class="text-sm text-red-500 font-bold"
                  >*</span>
                </label>

                <component
                  v-bind:is="field.field"
                  v-model="form[field.column_name]"
                  :field="field"
                  :errors="errors"
                  :formMeta="field.meta"
                  action="create"
                  @input="$set(form, field.column_name, $event)"
                  @extra="$set(form, $event.key, $event.value)"
                />

                <template v-if="errors[field.column_name].is">
                  <span v-for="error in errors[field.column_name].messages" :key="error" class="border-red-700 block px-2 py-2 text-sm text-red-100 bg-red-500">
                    {{ error }}
                  </span>
                </template>
              </div>
            </template>
          </div>

          <template v-if="customModules.length">
            <h3 class="text-xl leading-6 font-medium text-gray-900 mt-6">
              Custom Modules
            </h3>

            <div class="flex flex-wrap -mx-3 mb-4 pb-4 border-b border-gray-100">
              <div v-for="(module, index) in customModules" :key="`module-${index}`" class="w-full md:w-1/2 px-3 flex-col my-6">
                <label :for="`form-module-${module.slug}`" class="text-lg font-normal text-gray-700 w-auto">
                  {{ module.name }}
                </label>

                <Toggle :field="`form-module-${module.slug}`" v-model="formModules[module.slug].value" label="Enable Module" />

                <div v-if="formModules[module.slug].value" class="mt-6">
                  <h5 class="text-gray-600">Fields</h5>

                  <div v-for="field in module.fields" :key="`form-module-field-${module.slug}-${field.slug}`" class="mb-1">
                    <Toggle
                      :field="`form-module-field-${module.slug}-${field.slug}`"
                      v-model="formModules[module.slug].fields[field.slug]"
                      :label="field.name"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <template v-if="customPages.length">
            <h3 class="text-xl leading-6 font-medium text-gray-900 mt-6">
              Custom Pages
            </h3>

            <div class="flex flex-wrap -mx-3 mb-4">
              <div v-for="(page, index) in customPages" :key="`page-${index}`" class="w-full md:w-1/2 px-3 flex-col my-6">
                <label :for="`form-page-${page.slug}`" class="text-lg font-normal text-gray-700 w-auto">
                  {{ page.name }}
                </label>

                <Toggle :field="`form-page-${page.slug}`" v-model="formPages[page.slug].value" label="Enable Page" />

                <div v-if="formPages[page.slug].value" class="mt-6">
                  <h5 class="text-gray-600">Fields</h5>

                  <div v-for="field in page.fields" :key="`form-page-field-${page.slug}-${field.slug}`" class="mb-1">
                    <Toggle
                      :field="`form-page-field-${page.slug}-${field.slug}`"
                      v-model="formPages[page.slug].fields[field.slug]"
                      :label="field.name"
                    />
                  </div>
                </div>
              </div>
            </div>
          </template>

          <div class="flex">
            <button
              type="submit"
              class="w-full opacity-75 hover:opacity-100 text-xl font-bold bg-green-500 text-gray-100 py-4 px-6 block shadow-md"
            >
              <span>Save</span>
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import Layout from '@/Adaptcms/Base/ui/layouts/AdminLayout'
import AdminFormMixin from '@/Adaptcms/Base/ui/mixins/AdminFormMixin'
import ListAction from '@/Adaptcms/Base/ui/components/Table/Actions/ListAction'
import Toggle from '@/Adaptcms/Base/ui/components/Form/Toggle'
import * as SiteTypes from '@/Adaptcms/Site/ui/siteTypes'
import * as Fields from '@/Adaptcms/Site/ui/fields/AdminField'

export default {
  layout: (h, page) => h(Layout, [ page ]),

  props: [
    'siteType',
    'basicConfig',
    'customModules',
    'customPages'
  ],

  mixins: [
    AdminFormMixin
  ],

  components: {
    ListAction,
    Toggle,
    ...SiteTypes,
    ...Fields
  },

  data () {
    return {
      form: {},
      formModules: {},
      formPages: {},
      isLoaded: false
    }
  },

  methods: {
    submit () {
      let form = new FormData()

      for (let i in this.form) {
        let value = this.form[i]

        if (value && typeof value.length !== 'undefined' && typeof value === 'object') {
          for (let k = 0; k < value.length; k++) {
            let row = this.form[i][k]

            form.append(`${i}[${k}]`, row)
          }
        } else {
          form.append(i, value)
        }
      }

      // modules
      let modules = this.formModules

      for (let i in modules) {
        let module = modules[i]

        form.append(`modules[${i}][value]`, module.value)

        let fields = module.fields
        for (let k in fields) {
          let field = fields[k]

          form.append(`modules[${i}][fields][${k}]`, field)
        }
      }

      // pages
      let pages = this.formPages

      for (let i in pages) {
        let page = pages[i]

        form.append(`pages[${i}][value]`, page.value)

        let fields = page.fields
        for (let k in fields) {
          let field = fields[k]

          form.append(`pages[${i}][fields][${k}]`, field)
        }
      }

      // console.log(JSON.stringify(Object.fromEntries(form)))
      this.$inertia.post(this.$route('site_types.admin.post_activate', { siteType: this.siteType.id }), form)
    }
  },

  mounted () {
    // set up basic config
    let fields = this.basicConfig

    for (let i in fields) {
      let field = fields[i]

      this.$set(this.form, field.column_name, null)

      this.$set(this.errors, field.column_name, {
        is: false,
        messages: []
      })
    }

    // set up custom modules
    let modules = this.customModules

    for (let i in modules) {
      let module = modules[i]

      this.$set(this.formModules, module.slug, {
        value: module.value,
        fields: []
      })

      let fields = module.fields

      for (let k in fields) {
        let field = fields[k]

        this.$set(this.formModules[module.slug].fields, field.slug, field.value)
      }
    }

    // set up custom pages
    let pages = this.customPages

    for (let i in pages) {
      let page = pages[i]

      this.$set(this.formPages, page.slug, {
        value: page.value,
        fields: []
      })

      let fields = page.fields

      for (let k in fields) {
        let field = fields[k]

        this.$set(this.formPages[page.slug].fields, field.slug, field.value)
      }
    }

    this.isLoaded = true
  }
}
</script>
