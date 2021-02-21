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
                <i class="fab fa-github mr-1" />
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
