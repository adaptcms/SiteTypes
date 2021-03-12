<template>
  <div>
    <div class="bg-white shadow overflow-hidden sm:rounded-lg">
      <div class="flex flex-row justify-between px-4 py-5 border-b border-gray-200 sm:px-6">
        <div class="w-auto align-left">
          <h3 class="text-lg leading-6 font-medium text-gray-900">
            View Site Type
          </h3>
          <p class="mt-1 max-w-2xl text-sm leading-5 text-gray-500">

          </p>
        </div>

        <div class="w-auto flex-shrink-0 flex text-right">
          <EditAction route="site_types.admin.edit" :routeParams="{ siteType: siteType.id }" :classes="[ 'mr-1' ]" />
          <DeleteAction
            route="site_types.admin.delete"
            :routeParams="{ siteType: siteType.id }"
            modelName="siteType"
            :classes="[ 'mr-1' ]"
          />
          <ListAction route="site_types.admin.index" />
        </div>
      </div>
      <div>
        <dl>
          <div class="bg-gray-50 px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm leading-5 font-medium text-gray-500">
              Vendor
            </dt>
            <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
              {{ siteType.vendor }}
            </dd>
          </div>
          <div class="bg-gray-50 px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm leading-5 font-medium text-gray-500">
              Package
            </dt>
            <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
              {{ siteType.package }}
            </dd>
          </div>
          <div v-if="siteType.github_url" class="bg-gray-50 px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm leading-5 font-medium text-gray-500">
              GitHub
            </dt>
            <dd class="mt-1 text-sm leading-5 font-semibold sm:mt-0 sm:col-span-2">
              <a
                :href="siteType.github_url"
                target="_blank"
                rel="noopener"
                class="inline-block text-center bg-black text-white text-base rounded-full my-4 py-4 px-8 shadow-lg opacity-75 hover:opacity-100"
              >
                <svg
                  class="inline-block w-6 h-6 -mt-1 lg:mr-1"
                  xmlns="http://www.w3.org/2000/svg"
                  aria-label="GitHub"
                  role="img"
                  viewBox="0 0 512 512"
                >
                  <path fill="#fff" d="M335 499c14 0 12 17 12 17H165s-2-17 12-17c13 0 16-6 16-12l-1-50c-71 16-86-28-86-28-12-30-28-37-28-37-24-16 1-16 1-16 26 2 40 26 40 26 22 39 59 28 74 22 2-17 9-28 16-35-57-6-116-28-116-126 0-28 10-51 26-69-3-6-11-32 3-67 0 0 21-7 70 26 42-12 86-12 128 0 49-33 70-26 70-26 14 35 6 61 3 67 16 18 26 41 26 69 0 98-60 120-117 126 10 8 18 24 18 48l-1 70c0 6 3 12 16 12z"/>
                </svg>
                <span class="text-base">{{ siteType.vendor }}/{{ siteType.package }}</span>
              </a>
            </dd>
          </div>

          <div class="bg-white px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm leading-5 font-medium text-gray-500">
              Is Active?
            </dt>
            <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
              <div class="text-sm leading-5 font-semibold">
                <span v-if="siteType.is_active" class="text-green-500">
                  Yes
                </span>
                <span v-else class="text-red-500">
                  No
                </span>
              </div>
            </dd>
          </div>

          <div class="bg-gray-50 px-4 py-2 sm:grid sm:grid-cols-3 sm:gap-4 sm:px-6">
            <dt class="text-sm leading-5 font-medium text-gray-500">
              Created
            </dt>
            <dd class="mt-1 text-sm leading-5 text-gray-900 sm:mt-0 sm:col-span-2">
              {{ siteType.created_at | formatDate }}
            </dd>
          </div>
        </dl>
      </div>
    </div>
  </div>
</template>

<script>
import Layout from '@/Adaptcms/Base/ui/layouts/AdminLayout'
import AdminUtilityMixin from '@/Adaptcms/Base/ui/mixins/AdminUtilityMixin'
import EditAction from '@/Adaptcms/Base/ui/components/Table/Actions/EditAction'
import DeleteAction from '@/Adaptcms/Base/ui/components/Table/Actions/DeleteAction'
import ListAction from '@/Adaptcms/Base/ui/components/Table/Actions/ListAction'

export default {
  layout: (h, page) => h(Layout, [ page ]),

  props: [
    'siteType'
  ],

  mixins: [
    AdminUtilityMixin
  ],

  components: {
    EditAction,
    DeleteAction,
    ListAction
  }
}
</script>
