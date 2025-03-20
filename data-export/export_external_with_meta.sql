-- USE local_loop;
Select 
  n_fd.nid,
  n_fd.type,
  n_fd.title,
  pa.`alias` as relative_url,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as `changed`,
  -- doc_author.os2loop_documents_document_autho_value, -- not relevant for external
  -- doc_body.os2loop_documents_document_body_value  -- not relevant for external
  ext_desc.os2loop_external_descripti_value as `description`,
  ext_url.os2loop_external_url_title as url_title_text,
  ext_url.os2loop_external_url_uri as url,
  `subject`.`name` as `subject`,
  u_jt.os2loop_user_job_title_value as job_title,
  tags.tags,
  rev_date.os2loop_shared_rev_date_value as review_date
from (
  SELECT nid,vid,type,uid,title,created,changed 
  FROM node_field_data
  where type = 'os2loop_external'  -- nodes with links/external references (it seems)
    and status = 1
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
left join user__os2loop_user_job_title as u_jt on n_fd.uid = u_jt.entity_id
left join node__os2loop_external_descripti as ext_desc on n_fd.nid = ext_desc.entity_id
left join node__os2loop_external_url as ext_url on n_fd.nid = ext_url.entity_id
left join (
	SELECT 
		n_ss.entity_id,
		subject_tt_fd.name 
	FROM node__os2loop_shared_subject as n_ss
	left join taxonomy_term_field_data as subject_tt_fd on n_ss.os2loop_shared_subject_target_id = subject_tt_fd.tid
	where n_ss.bundle = 'os2loop_external') as `subject` on n_fd.nid = `subject`.entity_id
left join (
	SELECT 
		n_st.entity_id, 
        json_arrayagg(tt_fd.name) as tags 
	FROM node__os2loop_shared_tags  as n_st
	left join taxonomy_term_field_data as tt_fd on n_st.os2loop_shared_tags_target_id = tt_fd.tid
	where n_st.bundle = 'os2loop_external' 
	group by  n_st.entity_id) tags on n_fd.nid = tags.entity_id
left join node__os2loop_shared_rev_date as rev_date on n_fd.nid = rev_date.entity_id