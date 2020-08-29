<template>
<div class="p-card-body p-card-type-topic">
  <div class="main" v-if="!edit">
    <div class="p-entry-container">
      <div class="p-entry-favicon">
        <icon-type class="p-entry-icon"></icon-type>
      </div>
      <div class="p-entry-content">
        <span class="topic-content">{{ entry.title }}</span>
      </div>
      <div class="p-entry-actions">
        <span class="retry-btn" title="edit" v-if="!edit" @click.prevent="edit = true">
          <icon-edit></icon-edit>
        </span>            
        <div class="drag-handle">
          <icon-menu></icon-menu>
        </div>
      </div>
    </div>
  </div>
  <div class="main" v-else>
    <div class="edit-section">
      <label>
        <span>Title</span>
        <input type="text" placeholder="Title" name="title" 
          @keydown.enter.prevent="save()" 
          @keydown.esc="edit = false" 
          v-model="entry.title"/>
      </label>
    </div>
    <div class="edit-section edit-actions">
      <div>
        <a href="#" class="button button-primary" @click.prevent="save()">Save Changes</a>
        <a href="#" class="button" @click.prevent="edit = false">Cancel</a>
      </div>
      <div>
        <a href="#" class="delete-btn destructive" @click.prevent="deleteEntry()">Delete Entry</a>
      </div>
    </div>  
  </div>
</div>
</template>

<script>
import Menu from "../icons/Menu";
import Edit from "../icons/Edit";
import Type from "../icons/Type";

export default {
  props: ["entry"],
  data() {
    return {
      edit: false
    };
  },
  components: {
    "icon-menu": Menu,
    "icon-edit": Edit,
    "icon-type": Type
  },
  methods: {
    save: function() {
      this.edit = false;

      this.$parent.$emit("update:entry", this.entry);

      let payload = { title: this.entry.title };

      jQuery
        .post(
          podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          payload
        )
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    },
    deleteEntry: function() {
      this.$parent.$emit("delete:entry", this.entry);

      jQuery
        .ajax({
          url: podlove_vue.rest_url + "podlove/v1/shownotes/" + this.entry.id,
          method: "DELETE",
          dataType: "json"
        })
        .done(result => {})
        .fail(({ responseJSON }) => {
          console.error("could not delete entry:", responseJSON.message);
        });
    }
  }
};
</script>
