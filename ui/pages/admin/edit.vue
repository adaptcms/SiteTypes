<template>
  <div>
    <div class="bg-white shadow sm:rounded-lg">
      <div class="flex flex-row justify-between px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="w-auto align-left">
          <h3 class="text-2xl leading-6 font-medium text-gray-900">
            Edit Site Type
          </h3>
          <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">

          </p>
        </div>

        <div class="w-auto flex-shrink-0 flex text-right">
          <ListAction route="site_types.admin.index" />
        </div>
      </div>

      <form @submit.prevent="submit" class="px-4 py-5 sm:px-6 text-gray-500">
        <div class="flex flex-wrap -mx-3 mb-4">
          <div class="w-1/2 md:w-1/4 px-3 flex-col mb-4">
            <label for="form-vendor" class="text-lg font-normal text-gray-700 w-auto">
              Vendor
            </label>

            <span class="w-auto block text-gray-500 pt-2">
              {{ model.vendor }}
            </span>
          </div>

          <div class="w-1/2 md:w-1/4 px-3 flex-col mb-4">
            <label for="form-package" class="text-lg font-normal text-gray-700 w-auto">
              Package
            </label>

            <span class="w-auto block text-gray-500 pt-2">
              {{ model.package }}
            </span>
          </div>

          <div class="w-full md:w-1/2 px-3 flex-col mb-4">
            <label for="form-github_url" class="text-lg font-normal text-gray-700 w-auto">
              GitHub URL
            </label>

            <input
              type="text"
              id="form-github_url"
              class="text-base py-3 px-3 shadow-sm block mt-1 border w-full"
              v-model="form.github_url"
              :class="{ 'border-red-500': errors.github_url.is, 'border-gray-300': !errors.github_url.is }"
            />

            <template v-if="errors.github_url.is">
              <span v-for="error in errors.github_url.messages" class="border-red-700 block px-2 py-2 text-sm text-red-100 bg-red-500">
                {{ error }}
              </span>
            </template>
          </div>

          <div class="w-full md:w-1/6 px-3 flex-col mb-4">
            <div class="mt-8">
              <Toggle
                plugin="form-publish"
                label="Publish Package"
                v-model="form.publish"
                :customClass="{ 'border-red-500': errors.publish.is, 'border-gray-300': !errors.publish.is }"
                @update:modelValue="form.publish = $event"
              />
            </div>

            <template v-if="errors.publish.is">
              <span v-for="error in errors.publish.messages" class="border-red-700 block px-2 py-2 text-sm text-red-100 bg-red-500">
                {{ error }}
              </span>
            </template>
          </div>

          <div class="w-full md:w-1/6 px-3 flex-col mb-4">
            <div class="mt-8">
              <Toggle
                plugin="form-is_active"
                label="Is Active?"
                v-model="form.is_active"
                :customClass="{ 'border-red-500': errors.is_active.is, 'border-gray-300': !errors.is_active.is }"
                @update:modelValue="form.is_active = $event"
              />
            </div>

            <template v-if="errors.is_active.is">
              <span v-for="error in errors.is_active.messages" class="border-red-700 block px-2 py-2 text-sm text-red-100 bg-red-500">
                {{ error }}
              </span>
            </template>
          </div>
        </div>

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
</template>

<script>
import Layout from '@/Adaptcms/Base/ui/layouts/AdminLayout'
import AdminFormMixin from '@/Adaptcms/Base/ui/mixins/AdminFormMixin'
import ListAction from '@/Adaptcms/Base/ui/components/Table/Actions/ListAction'
import Toggle from '@/Adaptcms/Base/ui/components/Form/Toggle'

export default {
  layout: (h, page) => h(Layout, [ page ]),

  props: [
    'model'
  ],

  mixins: [
    AdminFormMixin
  ],

  components: {
    ListAction,
    Toggle
  },

  data () {
    return {
      form: {
        github_url: null,
        publish: false,
        is_active: false
      }
    }
  },

  methods: {
    submit () {
      this.$inertia.post(this.$route('site_types.admin.update', { siteType: this.model.id }), this.form)
    }
  }
}
</script>
