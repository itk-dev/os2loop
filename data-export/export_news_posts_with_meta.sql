Select
  n_fd.nid,
  n_fd.type,
  n_fd.title,
  pa.`alias` as relative_url,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as `changed`,
  -- doc_author.os2loop_documents_document_autho_value, -- is this okay to share, here we name person, not just orgs
  -- doc_body.os2loop_documents_document_body_value,  -- all delta is zero
  post_cont.os2loop_post_content_value as `content`,
  `subject`.`name` as `subject`,
  tags.tags
from (
  SELECT nid,vid,type,uid,title,created,changed
  FROM node_field_data
  where type = 'os2loop_post' -- nyheder/indlæg
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
-- left join node__os2loop_documents_document_autho as doc_author on n_fd.nid = doc_author.entity_id
-- left join node__os2loop_documents_document_body as doc_body on n_fd.nid = doc_body.entity_id
left join node__os2loop_post_content as post_cont on n_fd.nid = post_cont.entity_id
left join (
  SELECT
    n_ss.entity_id,
    subject_tt_fd.name
  FROM node__os2loop_shared_subject as n_ss
  left join taxonomy_term_field_data as subject_tt_fd on n_ss.os2loop_shared_subject_target_id = subject_tt_fd.tid
  where n_ss.bundle = 'os2loop_post') as `subject` on n_fd.nid = `subject`.entity_id
left join (
  SELECT
    n_st.entity_id,
        json_arrayagg(tt_fd.name) as tags
  FROM node__os2loop_shared_tags  as n_st
  left join taxonomy_term_field_data as tt_fd on n_st.os2loop_shared_tags_target_id = tt_fd.tid
  where n_st.bundle = 'os2loop_post'
  group by  n_st.entity_id) tags on n_fd.nid = tags.entity_id;
