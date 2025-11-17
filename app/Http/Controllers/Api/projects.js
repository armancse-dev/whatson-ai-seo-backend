import api from './axios';

export async function startClustering(projectId, maxClusters = 10) {
  const res = await api.post(`/projects/${projectId}/clusters/start`, { max_clusters: maxClusters });
  return res.data;
}

export async function fetchReports(projectId) {
  const res = await api.get(`/projects/${projectId}/reports`);
  return res.data;
}