<template>
  <!-- eslint-disable max-len -->
  <div>
    <PageHeader>
      <div>
        <div uk-grid>
          <div class="uk-width-expand">
            <h1>{{ $t('categories') }}</h1>
          </div>
          <div class="uk-width-1-1 uk-width-1-6@m">
            <div class="uk-flex uk-flex-right">
              <div v-if="$category.isAllowed('create')">
                <router-link class="uk-icon-button" :to="{ name : 'categories.create' }">
                  <i class="fas fa-plus"></i>
                </router-link>
              </div>
            </div>
          </div>
        </div>
      </div>
    </PageHeader>
    <section class="uk-section uk-section-small">
      <div class="uk-container uk-container-large">
        <div class="uk-grid-small uk-grid-margin-small uk-grid-stack" uk-grid>
          <div class="uk-width-1-1@m">
            <div class="uk-margin uk-text-center uk-child-width-1-1 uk-child-width-1-2@s uk-child-width-1-3@m uk-grid-medium uk-grid-match uk-flex-center" uk-height-match=".uk-card" uk-grid>
              <Card v-for="category in categories" :key="category.id" :category="category" />
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>
</template>

<script>
import PageHeader from '@/site/components/PageHeader.vue';
import Card from './components/Card.vue';

import messages from './lang';

import categoryStore from '@/stores/categories';
import registerModule from '@/stores/mixin';

export default {
  components: {
    PageHeader,
    Card
  },
  i18n: messages,
  mixins: [
    registerModule(
      {
        category: categoryStore
      }
    ),
  ],
  computed: {
    categories() {
      return this.$store.state.category.categories;
    },
    noData() {
      return this.categories && this.categories.length === 0;
    }
  },
  beforeRouteEnter(to, from, next) {
    next(async(vm) => {
      await vm.fetchData();
      next();
    });
  },
  async beforeRouteUpdate(to, from, next) {
    await this.fetchData();
    next();
  },
  methods: {
    fetchData() {
      this.$store.dispatch('category/browse');
    }
  }
};
</script>
