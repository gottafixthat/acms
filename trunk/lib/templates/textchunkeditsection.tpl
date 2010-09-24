<table width="100%">
<tr>
  <td>
     <a href="javascript:insertAt('{$ID}','::text::')"><img title="Center text" border="0" src="/static/icons/text_center.png" alt="Bold"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','__text__')"><img title="Boldface text" border="0" src="/static/icons/text_bold.png" alt="Bold"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','\'\'text\'\'')"><img title="Italicize" border="0" src="/static/icons/text_italic.png" alt="Italic"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','^^text^^')"><img title="Boxed text" border="0" src="/static/icons/text_boxed.png" alt="Boxed"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','-=text=-')"><img title="Title Bar" border="0" src="/static/icons/text_title.png" alt="Boxed"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','++text++')"><img title="Indent text" border="0" src="/static/icons/text_indent.png" alt="Indent"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','-+text+-')"><img title="Inset text" border="0" src="/static/icons/text_inset.png" alt="Inset"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','\n----\n')"><img title="Horizontal rule" border="0" src="/static/icons/text_hr.png" alt="Horizontal Rule"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','||r1c1|r1c2\nr2c1|r2c2||')"><img title="Table" border="0" src="/static/icons/text_table.png" alt="Table"></a>&nbsp;
     <a href="javascript:insertAt('{$ID}','[[page|text]]')"><img title="External Link" border="0" src="/static/icons/link.png" alt="Link"></a>&nbsp;
  </td>
</tr>
<tr>
  <td>
    <textarea id="{$ID}" name="{$Name}" rows="{$Rows}" cols="80" wrap>{$Chunk}</textarea>
  </td>
</tr>
</table>
