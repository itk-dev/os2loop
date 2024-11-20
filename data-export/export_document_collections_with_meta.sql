Select
  n_fd.nid,
  n_fd.type,
  n_fd.title,
  pa.`alias` as relative_url,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.created), '%Y-%m-%dT%H:%i:%s') as created,
  DATE_FORMAT(FROM_UNIXTIME(n_fd.changed), '%Y-%m-%dT%H:%i:%s') as `changed`,
  dci.document_node_ids,
  dci.document_relative_urls,
  docscol_content.os2loop_documents_dc_content_value as content, -- all format are rich text (html and div encoded)
  docs_ib.os2loop_documents_info_box_value as info_box,
  approval_date.os2loop_shared_approval_date_value as approval_date,
  `subject`.`name` as `subject`,
  tags.tags,
  `owner`.os2loop_shared_owner_value as `owner`,
  rev_date.os2loop_shared_rev_date_value as review_date,
  `version`.os2loop_shared_version_value as `version`
from (
  SELECT nid,vid,type,uid,title,created,changed
  FROM node_field_data
  where type = 'os2loop_documents_collection'
    -- the table os2loop_documents_collection_item associate document collections (their nid on collection_id)
  -- to documents (document_id = nid) except for 20 document collections. Fx case /rammedelegation nid=3807 it is a collection of
  -- links to sharepoint docs and /medicinhaandtering nid=3827 is a link to the collection
    -- /instruks-korrekt-haandtering-af-medicin-i-sundhed-og-omsorg-mso nid 4188
    -- of 805 documents 164 documents are not assigned to a document_collection
  ) as n_fd
left join path_alias as pa on CONCAT('/node/',n_fd.nid) = pa.path
left join (
  SELECT
    doc_col_itm.collection_id,
    json_arrayagg(doc_col_itm.document_id) as document_node_ids,
    json_arrayagg(pa.`alias`) as document_relative_urls
  from os2loop_documents_collection_item as doc_col_itm
  left join path_alias as pa on CONCAT('/node/',doc_col_itm.document_id) = pa.path
  group by doc_col_itm.collection_id
  ) as dci on n_fd.nid = dci.collection_id
left join node__os2loop_documents_dc_content as docscol_content on n_fd.nid = docscol_content.entity_id -- contains only records from bundle documents_collection (all delta 0, so top placement)
left join (
  SELECT
  entity_id,
  os2loop_documents_info_box_value
  FROM node__os2loop_documents_info_box
  WHERE bundle = 'os2loop_documents_collection') as docs_ib on n_fd.nid = docs_ib.entity_id  -- only from bundle document_collection
left join node__os2loop_shared_approval_date as approval_date on n_fd.nid = approval_date.entity_id
left join (
  SELECT
    n_ss.entity_id,
    subject_tt_fd.name
  FROM node__os2loop_shared_subject as n_ss
  left join taxonomy_term_field_data as subject_tt_fd on n_ss.os2loop_shared_subject_target_id = subject_tt_fd.tid
  where n_ss.bundle = 'os2loop_documents_collection') as `subject` on n_fd.nid = `subject`.entity_id
left join node__os2loop_shared_owner as `owner` on n_fd.nid = `owner`.entity_id
left join node__os2loop_shared_rev_date as rev_date on n_fd.nid = rev_date.entity_id
left join (
  SELECT
    n_st.entity_id,
        json_arrayagg(tt_fd.name) as tags
  FROM node__os2loop_shared_tags  as n_st
  left join taxonomy_term_field_data as tt_fd on n_st.os2loop_shared_tags_target_id = tt_fd.tid
  where n_st.bundle = 'os2loop_documents_collection'
  group by  n_st.entity_id) tags on n_fd.nid = tags.entity_id
left join node__os2loop_shared_version as `version` on n_fd.nid = `version`.entity_id
