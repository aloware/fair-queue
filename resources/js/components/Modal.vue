<script>
  export default {
    name: 'Modal',
    methods: {
      close() {
        this.$emit('close');
      },
    },
  };
</script>

<template>
  <transition name="modal-fade">
    <div class="fair-modal-backdrop">
      <div class="fair-modal"
        role="dialog"
        aria-labelledby="modalTitle"
        aria-describedby="modalDescription"
      >
        <header
          class="fair-modal-header"
          id="modalTitle"
        >
          <slot name="header">
            This is the default tile!
          </slot>
          <button
            type="button"
            class="btn-close"
            @click="close"
            aria-label="Close modal"
          >
            x
          </button>
        </header>

        <section
          class="fair-modal-body"
          id="modalDescription"
        >
          <slot name="body">
            This is the default body!
          </slot>
        </section>

        <footer class="fair-modal-footer">
          <slot name="footer">
            This is the default footer!
          </slot>
        </footer>
      </div>
    </div>
  </transition>
</template>

<style>
  .fair-modal-backdrop {
    position: fixed;
    top: 0;
    bottom: 0;
    left: 0;
    right: 0;
    background-color: rgba(0, 0, 0, 0.3);
    display: flex;
    justify-content: center;
    align-items: center;
  }

  .fair-modal {
    background: #FFFFFF;
    box-shadow: 2px 2px 20px 1px;
    overflow-x: auto;
    display: flex;
    flex-direction: column;
  }

  .fair-modal-header,
  .fair-modal-footer {
    padding: 15px;
    display: flex;
  }

  .fair-modal-header {
    position: relative;
    border-bottom: 1px solid #eeeeee;
    color: #7746ec;
    justify-content: space-between;
  }

  .fair-modal-footer {
    border-top: 1px solid #eeeeee;
    flex-direction: column;
  }

  .fair-modal-body {
    position: relative;
    padding: 20px 10px;
  }

  .btn-close {
    position: absolute;
    top: 0;
    right: 0;
    border: none;
    font-size: 20px;
    padding: 10px;
    cursor: pointer;
    font-weight: bold;
    color: #7746ec;
    background: transparent;
  }

  .btn-green {
    color: white;
    background: #7746ec;
    border: 1px solid #7746ec;
    border-radius: 2px;
  }

  .fair-modal-fade-enter,
  .fair-modal-fade-leave-to {
    opacity: 0;
  }

  .fair-modal-fade-enter-active,
  .fair-modal-fade-leave-active {
    transition: opacity .5s ease;
  }
</style>