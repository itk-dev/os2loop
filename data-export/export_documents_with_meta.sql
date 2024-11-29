Select 
  n_fd.nid,
  -- n_fd.type,
  n_fd.title,
  pa.`alias` as relative_url,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as `changed`,
  -- doc_author.os2loop_documents_document_autho_value as document_author, -- after meeting with Lasse Skjalms it is decided not to export, as end user can access this info fra url, if they 
                                                                           -- have appropriate rights, but it names persons, which makes it harder to share the exported data
  -- general authors are:
  --	Cura Book
  -- 	Digitalisering
  --  	Digitalisering og Kvalitet, MSO
  --	"Personnavn", Digitalisering
  -- 	"Personnavn"; Digitalisering
  -- 	"Personnavn" - SUE Område Marselisborg, "Personnavn" - SUE Område Marselisborg, "Personnavn" - SOL, "Personnavn" - SOL, "Personnavn" - SOL, "Personnavn" og "Personnavn" - Digitalisering
  -- 	"Personnavn" - SUE Område Marselisborg, "Personnavn" - SOL, "Personnavn" - SOL, Digitalisering
  -- 	"Personnavn", Digitalisering
  -- 	"Personnavn", Digitalisering
  -- 	Digitalisering og Kvalitet
  -- 	"Personnavn", Digitaliseringen
  -- 	Digtalisering
  -- 	Digitalisering - "Personnavn"
  -- 	Akuttilbud og Rehabilitering
  -- 	Kvalitet og Borgersikkerhed
  -- 	Digitaliseringen
  -- 	Digitalisering og Kvalitet, Sygeplejeklyngen
  -- 	"Personnavn", Digitalisering
  -- 	"Personnavn" - Digitalisering
  -- 	Digitalisering.
  -- 	Område Christiansbjerg og Kvalitet og Borgersikkerhed
  -- 	Digitalisering & kvalitet
  -- 	Medarbejdere i SOL
  -- 	Velfærdsteknologi
  -- 	Medarbejder i SOL
  -- 	Følgegruppen for medicin
  -- 	Borgervelfærd
  -- 	Velfærdssteknologi
  -- 	Digitalisering Kvalitet
  -- 	Digitalisering og Kvalitet.
  -- 	Demensklyngen
  -- 	Demensklyngen ("Personnavn")
  -- 	Demensklyngen ("Personnavn")
  -- 	Nissen
  -- 	Digitalisering og Kvalitet, Grøndalsvej 2
  -- 	Klyngen for sygepleje
  -- 	"Personnavn" - enhed for måltider og ernæring
  -- 	Sygeplejeklyngen
  -- 	"Personnavn"
  doc_body.os2loop_documents_document_body_value as document_body,  -- all delta is zero
  doc_content.content,  -- when 'content_type' is os2loop_documents_step_by_step the JSON object should be replaced by the corresponding folding_part entity depending on the content_target_id, otherwise the field can be removed
  `subject`.`name` as `subject`,
  tags.tags,
  approval_date.os2loop_shared_approval_date_value as approval_date,
  rev_date.os2loop_shared_rev_date_value as review_date,
  `version`.os2loop_shared_version_value as `version`
from (
  SELECT nid,vid,type,uid,title,created,changed 
  FROM node_field_data
  where `type` = 'os2loop_documents_document'
    and status = 1
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
-- left join node__os2loop_documents_document_autho as doc_author on n_fd.nid = doc_author.entity_id -- see comment in select
left join node__os2loop_documents_document_body as doc_body on n_fd.nid = doc_body.entity_id
-- group by n_fd.nid -- returns 701 records
left join (
	SELECT
		n_ddc.entity_id,
		-- ANY_VALUE(para_ifd.parent_field_name) as parent_field_name,
		json_arrayagg(json_object(
			'delta', n_ddc.delta, 
            'paragraph_title',  doc_title.os2loop_documents_title_value,  -- all delta is zero
			'paragraph_description', IFNull(doc_desc.os2loop_documents_description_value, IFNULL(tbl.os2loop_documents_tbl_desc_value, video.os2loop_video_description_value)),
			'paragraph', IFNull(doc_hc.os2loop_documents_hc_content_value, IFNull(text_and_image.os2loop_documents_tai_text_value, tbl.os2loop_documents_tbl_cont_value)),
			'content', json_object('content_target_id', n_ddc.os2loop_documents_document_conte_target_id,
				'content_type', para_ifd.`type`
                ), -- when 'content_type' is os2loop_documents_step_by_step the JSON object should be replaced by the corresponding folding_part entity depending on the content_target_id, otherwise the field can be removed
			'text_and_image_position', text_and_image.os2loop_documents_tai_position_value,
            'media', json_object('uri', IFNull(text_and_image.uri, video.os2loop_video_url_uri),
				'filemime', text_and_image.filemime,
				'filesize', text_and_image.filesize,
				'created', DATE_FORMAT(FROM_UNIXTIME(text_and_image.created), '%Y-%m-%dT%H:%i:%s'),
				'changed', DATE_FORMAT(FROM_UNIXTIME(text_and_image.`changed`), '%Y-%m-%dT%H:%i:%s'),
				'video_title', video.os2loop_video_title_value,
				'video_iframe', video.os2loop_video_iframe_value
				)
			)) as content
    FROM node__os2loop_documents_document_conte as n_ddc
    left join paragraphs_item_field_data as para_ifd on n_ddc.os2loop_documents_document_conte_target_id = para_ifd.id
	left join paragraph__os2loop_documents_hc_content as doc_hc on n_ddc.os2loop_documents_document_conte_target_id = doc_hc.entity_id
	left join (
		WITH media_attachments AS (SELECT
			media_collected.mid,
			fm.uri, -- where public:// -> https://loop.sundhedogomsorg.dk/sites/loop.sundhedogomsorg.dk/files/
			fm.filemime, 
			fm.filesize, 
			fm.created, 
			fm.changed 
		FROM (SELECT 
				m_fd.mid,
				IFNULL (m_mf.field_media_file_target_id, m_mi.field_media_image_target_id) as target_id
			from media_field_data as m_fd
			left join media__field_media_file as m_mf on m_fd.mid = m_mf.entity_id -- remember dep on m_fd.bundle whether it should be media__field_media_file, media__field_media_image or media__field_media_library we need to join on
			left join media__field_media_image as m_mi on m_fd.mid = m_mi.entity_id
			-- left join media__field_media_library as m_ml on m_fd.mid = m_ml.entity_id -- turns out the media_library is not the correct reference for the file_managed table
			) as media_collected
		left join file_managed as fm on media_collected.target_id = fm.fid)
		SELECT
			tai_pos.bundle,
			tai_pos.deleted,
			tai_pos.entity_id,
			tai_pos.revision_id,
			tai_pos.langcode,
			tai_pos.delta,
			tai_pos.os2loop_documents_tai_position_value,
			tai_text.os2loop_documents_tai_text_value,
			tai_text.os2loop_documents_tai_text_format,
			tai_image.os2loop_documents_tai_image_target_id,
			media_attachments.uri,
			media_attachments.filemime,
			media_attachments.filesize,
			media_attachments.created,
			media_attachments.`changed`
		FROM paragraph__os2loop_documents_tai_position as tai_pos
		left join paragraph__os2loop_documents_tai_text as tai_text on tai_pos.entity_id = tai_text.entity_id
		-- where tai_pos.bundle != tai_text.bundle -- none
		-- where tai_pos.entity_id != tai_text.entity_id -- none
		-- where tai_pos.delta != tai_text.delta -- none
		left join paragraph__os2loop_documents_tai_image as tai_image on tai_pos.entity_id = tai_image.entity_id
		-- where tai_pos.bundle != tai_image.bundle -- none
		-- where tai_pos.entity_id != tai_image.entity_id -- none
		-- where tai_pos.delta != tai_image.delta -- none
		-- where !isnull(tai_text.os2loop_documents_tai_text_format) -- 113 records - like numbner of records in tai_text
		-- where !isnull(tai_image.os2loop_documents_tai_image_target_id) -- 60 records - like numbner of records in tai_image
		left join media_attachments on tai_image.os2loop_documents_tai_image_target_id = media_attachments.mid
		) as text_and_image on n_ddc.os2loop_documents_document_conte_target_id = text_and_image.entity_id
	left join (
		SELECT 
			video_src.bundle,
			video_src.entity_id,
			video_src.delta,
			video_title.os2loop_video_title_value,
			video_desc.os2loop_video_description_value, 
			video_ifrm.os2loop_video_iframe_value,
			video_url.os2loop_video_url_uri,
			-- video_url.os2loop_video_url_title -- all empty so far
			-- Convert(video_url.os2loop_video_url_options, CHAR(255)), -- nothing interesting so far (all contains "a:0:{}")
			video_width.os2loop_video_width_value
		FROM paragraph__os2loop_video_source_type as video_src -- so far all sources are url
		left join paragraph__os2loop_video_description as video_desc on video_src.entity_id = video_desc.entity_id
		left join paragraph__os2loop_video_iframe as video_ifrm on video_src.entity_id = video_ifrm.entity_id
		left join paragraph__os2loop_video_title as video_title on video_src.entity_id = video_title.entity_id
		left join paragraph__os2loop_video_url as video_url on video_src.entity_id = video_url.entity_id
		left join paragraph__os2loop_video_width as video_width on video_src.entity_id = video_width.entity_id
		) as video on n_ddc.os2loop_documents_document_conte_target_id = video.entity_id
	-- where para_ifd.type = 'os2loop_video' -- returns 10 of 14 video records
	left join (
		SELECT 
			tbl_content.bundle,
			tbl_content.entity_id,
			tbl_content.delta,
			tbl_content.os2loop_documents_tbl_cont_value,
			tbl_content.os2loop_documents_tbl_cont_format,
			tbl_desc.os2loop_documents_tbl_desc_value
		FROM paragraph__os2loop_documents_tbl_cont as tbl_content
		left join paragraph__os2loop_documents_tbl_desc as tbl_desc on tbl_content.entity_id = tbl_desc.entity_id
		) as tbl on n_ddc.os2loop_documents_document_conte_target_id = tbl.entity_id
	-- where para_ifd.`type` = 'os2loop_documents_table' -- returns 3 of 16 records
    left join paragraph__os2loop_documents_title as doc_title on n_ddc.os2loop_documents_document_conte_target_id = doc_title.entity_id
	left join paragraph__os2loop_documents_description as doc_desc on n_ddc.os2loop_documents_document_conte_target_id = doc_desc.entity_id -- all para doc desc relates to bundle step_by_step
	-- we miss content on step by step paragraphs, (we only have description so far) this must be added post querying using the dedicated query export_folding_parts_of_documents after it have been post-processed by the preprocess_loop_document_folding_parts.py
	-- we ignore table of contents as this does not add any additional information conveying text
	where para_ifd.`type` not in (
		'table_of_contents' -- this leaves out 3 records
		)
	group by n_ddc.entity_id
	) as doc_content on n_fd.nid = doc_content.entity_id
-- where ((not ISNULL(doc_body.os2loop_documents_document_body_value)) and not ISNULL(n_ddc.os2loop_documents_document_conte_target_id)) -- returns 13 records, where 3 (maybe 4) records seems to have both relevant body and content
left join node__os2loop_shared_approval_date as approval_date on n_fd.nid = approval_date.entity_id
left join (
	SELECT 
		n_ss.entity_id,
		subject_tt_fd.name 
	FROM node__os2loop_shared_subject as n_ss
	left join taxonomy_term_field_data as subject_tt_fd on n_ss.os2loop_shared_subject_target_id = subject_tt_fd.tid
	where n_ss.bundle = 'os2loop_documents_document') as `subject` on n_fd.nid = `subject`.entity_id
left join (
	SELECT 
		n_st.entity_id, 
        json_arrayagg(tt_fd.name) as tags 
	FROM node__os2loop_shared_tags  as n_st
	left join taxonomy_term_field_data as tt_fd on n_st.os2loop_shared_tags_target_id = tt_fd.tid
	where n_st.bundle = 'os2loop_documents_document' 
	group by  n_st.entity_id) tags on n_fd.nid = tags.entity_id
left join node__os2loop_shared_rev_date as rev_date on n_fd.nid = rev_date.entity_id
left join node__os2loop_shared_version as `version` on n_fd.nid = `version`.entity_id