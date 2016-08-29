{extends file="layout.tpl"}

{block name=title}
  {if $artist->id}
    Editare autor
  {else}
    Adăugare autor
  {/if}
{/block}

{block name=banner}{/block}
{block name=search}{/block}

{block name=content}
  <div class="panel panel-default">
    <div class="panel-heading">
      {if $artist->id}
        Editare autor
      {else}
        Adăugare autor
      {/if}
    </div>

    <div class="panel-body">
      <form method="post">
        <input type="hidden" name="id" value="{$artist->id}"/>

        <div class="form-group">
          <label>Nume</label>
          <input type="text" name="name" value="{$artist->name}" size="50" class="form-control" />
        </div>
        <div class="form-group">
          <label>E-mail</label>
          <input type="text" name="email" value="{$artist->email}" size="50" class="form-control" />
        </div>
        <div class="form-group">
          <label>Cod</label>
          <input type="text" name="label" value="{$artist->label}" size="30" class="form-control" />
        </div>
        <div class="form-group">
          <label>Credite</label>
          <input type="text" name="credits" value="{$artist->credits|escape}" size="80" class="form-control" />
        </div>
        <input class="btn btn-primary" type="submit" name="submitButton" value="salvează" />
        <a class="btn btn-link" href="autori-imagini.php">înapoi la lista de autori</a>

      </form>
    </div>
  </div>
{/block}
