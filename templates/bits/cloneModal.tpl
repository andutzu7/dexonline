{$object=$object|default:''}
{$desc=$desc|default:''}
<div class="modal fade" id="cloneModal" tabindex="-1" role="dialog">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <form method="post" role="form">
        <div class="modal-header">
          <h4 class="modal-title">Clonează {$desc}</h4>
          <button
            type="button"
            class="btn-close"
            data-bs-dismiss="modal"
            aria-label="Close">
          </button>
        </div>

        <div class="modal-body">
          {include "bits/clone{$object}Form.tpl"}
        </div>

        <div class="modal-footer">
          <button type="submit" class="btn btn-primary" name="cloneButton">
            <i class="glyphicon glyphicon-duplicate"></i>
            clonează
          </button>
          <button type="button" class="btn btn-link" data-bs-dismiss="modal">
            <i class="glyphicon glyphicon-remove"></i>
            renunță
          </button>
        </div>
      </form>
    </div>
  </div>
</div>
