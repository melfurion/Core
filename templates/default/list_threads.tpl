<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Goto}:</span>&nbsp;{IF URL->INDEX}<a class="PhorumNavLink" href="{URL->INDEX}">{LANG->ForumList}</a>&bull;{/IF}<a class="PhorumNavLink" href="{URL->POST}">{LANG->NewTopic}</a>&bull;<a class="PhorumNavLink" href="{URL->SEARCH}">{LANG->Search}</a>&bull;{INCLUDE "loginout_menu"}
</div>
{IF PAGES}
  <div class="PhorumNavBlock" style="text-align: left;">
    <div style="float: right;">
      <span class="PhorumNavHeading">{LANG->Pages}:</span>&nbsp;{IF URL->PREVPAGE}<a class="PhorumNavLink" href="{URL->PREVPAGE}">{LANG->PrevPage}</a>{/IF}{IF URL->FIRSTPAGE}<a class="PhorumNavLink" href="{URL->FIRSTPAGE}">{LANG->FirstPage}...</a>{/IF}{LOOP PAGES}{IF PAGES->pageno CURRENTPAGE}<span class="PhorumNavLink"><strong>{PAGES->pageno}</strong></span>{ELSE}<a class="PhorumNavLink" href="{PAGES->url}">{PAGES->pageno}</a>{/IF}{/LOOP PAGES}{IF URL->LASTPAGE}<a class="PhorumNavLink" href="{URL->LASTPAGE}">...{LANG->LastPage}</a>{/IF}{IF URL->NEXTPAGE}<a class="PhorumNavLink" href="{URL->NEXTPAGE}">{LANG->NextPage}</a>{/IF}
    </div>
    <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->CurrentPage}:</span>{CURRENTPAGE} {LANG->of} {TOTALPAGES}
  </div>
{/IF}
<table class="PhorumStdTable" cellspacing="0">
  <tr>
    <th class="PhorumTableHeader" align="left">{LANG->Subject}</th>
    {IF VIEWCOUNT_COLUMN}
      <th class="PhorumTableHeader" align="center">{LANG->Views}</th>
    {/IF}
    <th class="PhorumTableHeader" align="left" nowrap>{LANG->WrittenBy}</th>
    <th class="PhorumTableHeader" align="left" nowrap>{LANG->Posted}</th>
  </tr>
  <?php $oldthread=0; $rclass=""; ?>
  {LOOP MESSAGES}
    <?php
      if($oldthread != $PHORUM['TMP']['MESSAGES']['thread']){
        if($rclass=="Alt") $rclass=""; else $rclass="Alt";
        $oldthread=$PHORUM['TMP']['MESSAGES']['thread'];
      }
    ?>
    <tr>
    <td class="PhorumTableRow<?php echo $rclass;?>" style="padding-left: {MESSAGES->indent_cnt}px">&nbsp;{MESSAGES->indent}{marker}
      {IF MESSAGES->sort PHORUM_SORT_STICKY}
        <span class="PhorumListSubjPrefix">{LANG->Sticky}:</span>
      {/IF}
      {IF MESSAGES->sort PHORUM_SORT_ANNOUNCEMENT}
        <span class="PhorumListSubjPrefix">{LANG->Announcement}:</span>
      {/IF}
      {IF MESSAGES->moved}
        <span class="PhorumListSubjPrefix">{LANG->MovedSubject}:</span>
      {/IF}
      <a href="{MESSAGES->url}">{MESSAGES->subject}</a>&nbsp;<span class="PhorumNewFlag">{MESSAGES->new}</span>
    </td>
      {IF VIEWCOUNT_COLUMN}
        <td class="PhorumTableRow<?php echo $rclass;?>" nowrap="nowrap" align="center" width="80">{MESSAGES->viewcount}</td>
      {/IF}
      <td class="PhorumTableRow<?php echo $rclass;?>" nowrap="nowrap" width="150">
        {MESSAGES->linked_author}
      </td>
      <td class="PhorumTableRow<?php echo $rclass;?> PhorumSmallFont" nowrap="nowrap" width="150">
        {MESSAGES->datestamp}
        {IF MODERATOR true}
          <br />
          <span class="PhorumListModLink">
            {IF MESSAGES->threadstart false}
              <a class="PhorumListModLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteMessage}')) window.location='{MESSAGES->delete_url1}';">{LANG->DeleteMessage}</a>
            {/IF}
            {IF MESSAGES->threadstart true}
              <a class="PhorumListModLink" href="javascript:if(window.confirm('{LANG->ConfirmDeleteThread}')) window.location='{MESSAGES->delete_url2}';">{LANG->DeleteThread}</a>{IF MESSAGES->move_url}&nbsp;|&nbsp;<a class="PhorumListModLink" href="{MESSAGES->move_url}">{LANG->MoveThread}</a>{/IF}&nbsp;|&nbsp;<a class="PhorumListModLink" href="{MESSAGES->merge_url}">{LANG->MergeThread}</a>{/IF}
          </span>
        {/IF}
      </td>
    </tr>
  {/LOOP MESSAGES}
</table>
{INCLUDE "paging"}
<div class="PhorumNavBlock" style="text-align: left;">
  <span class="PhorumNavHeading PhorumHeadingLeft">{LANG->Options}:</span>&nbsp;{IF LOGGEDIN true}<a class="PhorumNavLink" href="{URL->MARKREAD}">{LANG->MarkRead}</a>{/IF}
</div>
