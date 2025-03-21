Select 
  n_fd.nid,
  n_fd.type,
  n_fd.title,
  pa.`alias` as relative_url,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as `changed`,
  -- doc_author.os2loop_documents_document_autho_value, -- is this okay to share, here we name person, not just orgs
  -- doc_body.os2loop_documents_document_body_value,  -- all delta is zero
  -- page_cont.os2loop_page_content_summary, -- no info at the moment
  page_cont.os2loop_page_content_value as page_content,
  sec_page_para.os2loop_section_page_free_html_value, 
  sec_page_para.os2loop_section_page_info_text_value, 
  sec_page_para.os2loop_section_page_view_header_value,
  sec_page_para.os2loop_section_page_view_text_value
from (
  SELECT nid,vid,type,uid,title,created,changed 
  FROM node_field_data
  where type in (
    'os2loop_page', -- static pages
    'os2loop_section_page' -- only 3 nodes, but /velkommen-til-loop (landing page) and /arbejdsgange-og-vejledninger and /sundhedsfaglige-instrukser - collection pages
    ) 
    and status = 1
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
-- left join node__os2loop_documents_document_autho as doc_author on n_fd.nid = doc_author.entity_id
-- left join node__os2loop_documents_document_body as doc_body on n_fd.nid = doc_body.entity_id
left join node__os2loop_page_content as page_cont on n_fd.nid = page_cont.entity_id
left join (SELECT
	  -- sec_page_para.bundle as sec_page_para_bundle, 
	  -- IFNULL(sec_page_free.bundle, IFNULL(sec_page_info.bundle, sec_page_view.bundle)) as sec_page_bundle, 
	  -- sec_page_para.deleted, 
	  sec_page_para.entity_id, 
	  -- sec_page_para.revision_id, sec_page_para.langcode, sec_page_para.delta, sec_page_para.os2loop_section_page_paragraph_target_id, sec_page_para.os2loop_section_page_paragraph_target_revision_id, 
	  -- IFNull(sec_page_free.entity_id,IFNULL(sec_page_info.entity_id, sec_page_view.entity_id)) as para_entity_id, 
	  -- IFNull(sec_page_free.revision_id, IFNULL(sec_page_info.revision_id, sec_page_view.revision_id)) as para_revision_id, 
	  -- IFNULL(sec_page_free.deleted, IFNULL(sec_page_info.deleted, sec_page_view.deleted)) as deleted,
	  -- IFNull(sec_page_free.langcode, IFNULL(sec_page_info.langcode, sec_page_view.langcode)) as langcode, 
	  -- IFNull(sec_page_free.delta, IFNULL(sec_page_info.delta, sec_page_view.delta)) as delta, 
	  sec_page_free.os2loop_section_page_free_html_value, 
	  sec_page_info.os2loop_section_page_info_text_value, 
	  sec_page_view.os2loop_section_page_view_header_value,
	  sec_page_view.os2loop_section_page_view_text_value
	  -- sec_page_free.os2loop_section_page_free_html_format, 
	  -- sec_page_info.os2loop_section_page_info_text_format,
	  -- sec_page_view.os2loop_section_page_view_text_format
	  -- par_ifd.id as para_id, par_ifd.revision_id, par_ifd.type as para_type, par_ifd.parent_id as para_parent_id, par_ifd.parent_type, par_ifd.parent_field_name, par_ifd.langcode, par_ifd.status, par_ifd.created,  par_ifd.behavior_settings, par_ifd.default_langcode, par_ifd.revision_translation_affected, 
	FROM node__os2loop_section_page_paragraph as sec_page_para
	-- ::: the info in paragraphs_item_field_data are not needed here :::
	-- left join (SELECT *
	-- FROM paragraphs_item_field_data
	-- where parent_field_name = 'os2loop_section_page_paragraph') as par_ifd on sec_page_para.os2loop_section_page_paragraph_target_id = par_ifd.id 
	left join paragraph__os2loop_section_page_free_html as sec_page_free on sec_page_para.os2loop_section_page_paragraph_target_id = sec_page_free.entity_id
	left join paragraph__os2loop_section_page_info_text as sec_page_info on sec_page_para.os2loop_section_page_paragraph_target_id = sec_page_info.entity_id
	-- left join paragraph__os2loop_section_page_title -- empty table, so no paragraph section page titles
	left join (SELECT 
		-- sec_page_header.bundle,
		sec_page_header.entity_id,
		-- sec_page_header.revision_id,
		-- sec_page_header.deleted,
		-- sec_page_header.langcode,
		-- sec_page_header.delta,
		sec_page_header.os2loop_section_page_view_header_value,
		sec_page_text.os2loop_section_page_view_text_value,
		sec_page_text.os2loop_section_page_view_text_format
	  FROM paragraph__os2loop_section_page_view_header as sec_page_header 
	  left join paragraph__os2loop_section_page_view_text as sec_page_text on sec_page_header.entity_id = sec_page_text.entity_id) as sec_page_view on sec_page_para.os2loop_section_page_paragraph_target_id = sec_page_view.entity_id
	) as sec_page_para on n_fd.nid = sec_page_para.entity_id