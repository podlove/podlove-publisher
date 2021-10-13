<template>
  <div>
    <div
      v-if="!edit"
      class="flex items-center px-3 py-2.5 mx-5 mt-8 mb-0 max-w-3xl gap-3"
    >
      <div class="text-gray-500 cursor-move">
        <icon-menu htmlClass="w-5 h-5 drag-handle" />
      </div>
      <div class="flex-grow font-bold text-base border-b-2 border-gray-800">
        {{ entry.title }}
      </div>
      <div class="text-gray-500 cursor-pointer" @click.prevent="edit = true">
        <icon-edit htmlClass="w-5 h-5" />
      </div>
    </div>
    <!-- Topic edit -->
    <sn-card v-else>
      <div class="flex items-center justify-between">
        <div class="w-full">
          <label
            for="topic_title"
            class="block text-sm font-medium text-gray-700"
          >
            Title
          </label>
          <div class="mt-1">
            <input
              @keydown.enter.prevent="save()"
              @keydown.esc="edit = false"
              v-model="entry.title"
              type="text"
              name="topic_title"
              id="topic_title"
              class="
                shadow-sm
                focus:ring-blue-500 focus:border-blue-500
                block
                w-full
                sm:text-sm
                border-gray-300
                rounded-md
              "
            />
          </div>
        </div>
      </div>

      <div class="h-8 w-full border-b border-gray-300"></div>

      <div class="pt-5">
        <div class="flex justify-between">
          <div>
            <sn-button type="danger" :onClick="deleteEntry"
              >Delete Topic</sn-button
            >
          </div>
          <div>
            <div class="flex justify-end">
              <sn-button
                :onClick="
                  () => {
                    edit = false;
                  }
                "
                >Cancel</sn-button
              >

              <sn-button type="primary" :onClick="save" htmlClass="ml-3"
                >Save</sn-button
              >
            </div>
          </div>
        </div>
      </div>
    </sn-card>
  </div>
</template>

<script>
import Menu from "../icons/Menu";
import Edit from "../icons/Edit";
import Type from "../icons/Type";
import SNButton from "./sn-button.vue";
import SNCard from "./sn-card.vue";

export default {
  props: ["entry"],
  data() {
    return {
      edit: false,
    };
  },
  components: {
    "icon-menu": Menu,
    "icon-edit": Edit,
    "icon-type": Type,
    "sn-button": SNButton,
    "sn-card": SNCard,
  },
  methods: {
    save: function () {
      this.edit = false;

      this.$parent.$emit("update:entry", this.entry);

      let payload = { title: this.entry.title };

      jQuery
        .post(
          podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          payload
        )
        .done((result) => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
    deleteEntry: function () {
      this.$parent.$emit("delete:entry", this.entry);

      jQuery
        .ajax({
          url: podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          method: "DELETE",
          dataType: "json",
        })
        .done((result) => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
  },
};
</script>
