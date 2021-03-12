<template>
  <div>
    <div class="flex flex-col lg:flex-row justify-between pb-6">
      <div class="w-full lg:w-1/3 align-left">
        <h2 class="text-3xl text-gray-800 text-center lg:text-left mb-4 lg:mb-0">
          Site Types
        </h2>
      </div>

      <div class="w-full lg:w-1/3 flex-shrink-0 mb-2 lg:mb-0">
        <MarketplaceSearch
          category="Site Type"
          installRoute="site_types.admin.install"
          :marketplace_uri="marketplace_uri"
        />
      </div>

      <div class="w-full lg:w-auto flex-shrink-0 flex flex-col lg:flex-row">
        <SearchInput
          v-if="items.data.length"
          route="site_types.admin.search"
          model="siteType"
        />

        <CreateButton route="site_types.admin.create" label="Create Site Type" />
      </div>
    </div>

    <NoItems v-if="!items.data.length" label="Site Types" />

    <template v-if="items.data.length">
      <TableStart>
        <thead>
          <tr>
            <HeaderColumn label="Vendor" field="vendor" :sortBy="sortBy" :sortDir="sortDir" />
            <HeaderColumn label="Package" field="package" :sortBy="sortBy" :sortDir="sortDir" />
            <HeaderColumn label="GitHub" />
            <HeaderColumn label="Created" field="created_at" :sortBy="sortBy" :sortDir="sortDir" />
            <EmptyHeaderColumn />
            <EmptyHeaderColumn />
          </tr>
        </thead>
        <tbody class="bg-white">
          <tr
            v-for="siteType in items.data"
            :key="siteType.id"
            class="hover:bg-blue-100"
          >
            <TableColumn>
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ siteType.vendor }}
              </div>
            </TableColumn>
            <TableColumn>
              <div class="text-sm leading-5 font-medium text-gray-900">
                {{ siteType.package }}
              </div>
            </TableColumn>
            <TableColumn>
              <template v-if="siteType.github_url">
                <a
                  :href="siteType.github_url"
                  target="_blank"
                  rel="noopener"
                  class="inline-block text-center bg-black text-white text-base rounded-full py-2 px-4 lg:my-4 lg:py-4 lg:px-8 shadow-lg opacity-75 hover:opacity-100"
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
                  <span class="hidden lg:inline-block text-base">{{ siteType.vendor }}/{{ siteType.package }}</span>
                </a>
              </template>
            </TableColumn>
            <TableColumn>
              <span class="text-sm leading-5 text-gray-500">
                {{ siteType.created_at | formatDate }}
              </span>
            </TableColumn>
            <TableColumn>
              <inertia-link
                v-if="!siteType.is_active"
                :href="$route('site_types.admin.show_activate', { siteType: siteType.id })"
                class="inline-block text-center bg-pink-500 text-white text-base font-bold rounded-full py-2 px-4 lg:my-4 lg:py-4 lg:px-8 shadow-lg opacity-75 hover:opacity-100"

              >
                Activate
              </inertia-link>

              <button
                v-if="siteType.is_active"
                type="button"
                class="inline-block text-center bg-gray-500 text-white text-base font-bold rounded-full py-2 px-4 lg:my-4 lg:py-4 lg:px-8 shadow-lg opacity-75 hover:opacity-100"
                @click.prevent="deactivateSiteType(siteType)"
              >
                Deactivate
              </button>
            </TableColumn>
            <TableColumn customClass="text-right">
              <ConfigAction
                v-if="siteType.is_active"
                permission="site_types.admin.settings"
                route="site_types.admin.show_settings"
                :routeParams="{ siteType: siteType.id }"
              />
              <ShowAction route="site_types.admin.show" :routeParams="{ siteType: siteType.id }" />
              <EditAction route="site_types.admin.edit" :routeParams="{ siteType: siteType.id }" />
              <DeleteAction
                route="site_types.admin.delete"
                :routeParams="{ siteType: siteType.id }"
                modelName="siteType"
              />
            </TableColumn>
          </tr>
        </tbody>
      </TableStart>

      <Pagination :collection="items" />
    </template>
  </div>
</template>

<script>
import Layout from '@/Adaptcms/Base/ui/layouts/AdminLayout'
import AdminUtilityMixin from '@/Adaptcms/Base/ui/mixins/AdminUtilityMixin'
import EmptyHeaderColumn from '@/Adaptcms/Base/ui/components/Table/EmptyHeaderColumn'
import HeaderColumn from '@/Adaptcms/Base/ui/components/Table/HeaderColumn'
import TableColumn from '@/Adaptcms/Base/ui/components/Table/TableColumn'
import TableStart from '@/Adaptcms/Base/ui/components/Table/TableStart'
import EditAction from '@/Adaptcms/Base/ui/components/Table/Actions/EditAction'
import DeleteAction from '@/Adaptcms/Base/ui/components/Table/Actions/DeleteAction'
import ShowAction from '@/Adaptcms/Base/ui/components/Table/Actions/ShowAction'
import ConfigAction from '@/Adaptcms/Base/ui/components/Table/Actions/ConfigAction'
import CreateButton from '@/Adaptcms/Base/ui/components/Index/CreateButton'
import NoItems from '@/Adaptcms/Base/ui/components/Index/NoItems'
import Pagination from '@/Adaptcms/Base/ui/components/Pagination'
import SearchInput from '@/Adaptcms/Base/ui/components/SearchInput'
import MarketplaceSearch from '@/Adaptcms/Base/ui/components/Marketplace/MarketplaceSearch'

export default {
  layout: (h, page) => h(Layout, [ page ]),

  props: [
    'items',
    'sortBy',
    'sortDir',
    'marketplace_uri'
  ],

  mixins: [
    AdminUtilityMixin
  ],

  components: {
    ConfigAction,
    CreateButton,
    EditAction,
    DeleteAction,
    EmptyHeaderColumn,
    HeaderColumn,
    MarketplaceSearch,
    NoItems,
    Pagination,
    SearchInput,
    ShowAction,
    TableColumn,
    TableStart
  },

  methods: {
    async deactivateSiteType (siteType) {
      if (confirm('Are you sure you want to deactivate this site type? Data related to this site type will be PERMANENTLY DELETED.')) {
        let url = this.$route('site_types.admin.update', { siteType: siteType.id })

        this.$inertia.post(url, {
          is_active: false
        })
      }
    }
  }
}
</script>
