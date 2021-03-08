<template>
  <div v-if="isLoaded">
    <div class="bg-white shadow sm:rounded-lg">
      <div class="flex flex-row justify-between px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="w-auto align-left">
          <h3 class="text-2xl leading-6 font-medium text-gray-900">
            Site Type Settings
          </h3>
          <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">
            {{ siteType.vendor }}/{{ siteType.package }}
          </p>
        </div>

        <div class="w-auto flex-shrink-0 flex text-right">
          <ListAction route="site_types.admin.index" />
        </div>
      </div>

      <form @submit.prevent="submit" class="px-4 py-5 sm:px-6 text-gray-500">
        <div class="flex flex-wrap -mx-3 mb-4 pb-4 border-b border-gray-100">
          <template v-for="field in config">
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
                :formMeta="formMeta"
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

        <div class="mt-4">
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
</template>

<script>
import { isEmpty } from 'lodash'
import Layout from '@/Adaptcms/Base/ui/layouts/AdminLayout'
import AdminFormMixin from '@/Adaptcms/Base/ui/mixins/AdminFormMixin'
import ListAction from '@/Adaptcms/Base/ui/components/Table/Actions/ListAction'
import * as Fields from '@/Adaptcms/Site/ui/fields/AdminField'

export default {
  layout: (h, page) => h(Layout, [ page ]),

  props: [
    'siteType',
    'config',
    'settings',
    'formMeta'
  ],

  mixins: [
    AdminFormMixin
  ],

  components: {
    ListAction,
    ...Fields
  },

  data () {
    return {
      form: {},
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

      this.$inertia.post(this.$route('site_types.admin.update_settings', { siteType: this.siteType.id }), form)
    }
  },

  mounted () {
    // set up basic config
    let fields = this.config

    for (let i in fields) {
      let field = fields[i]

      this.$set(this.form, field.column_name, this.settings[field.column_name])

      this.$set(this.errors, field.column_name, {
        is: false,
        messages: []
      })
    }

    this.isLoaded = true
  }
}
</script>
