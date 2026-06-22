import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, FlatList, TouchableOpacity, ActivityIndicator, Alert, Modal, TextInput, ScrollView } from 'react-native';
import AsyncStorage from '@react-native-async-storage/async-storage';

export default function VetAppointments() {
  const [appointments, setAppointments] = useState([]);
  const [loading, setLoading] = useState(true);
  
  const [modalVisible, setModalVisible] = useState(false);
  const [cancelModalVisible, setCancelModalVisible] = useState(false);
  const [petModalVisible, setPetModalVisible] = useState(false);
  
  const [selectedPet, setSelectedPet] = useState(null);
  const [petInfo, setPetInfo] = useState(null);
  const [infoLoading, setInfoLoading] = useState(false);

  const [treatment, setTreatment] = useState({ title: '', description: '', next_control_date: '' });
  const [cancelReason, setCancelReason] = useState('');

  const fetchData = async () => {
    setLoading(true);
    try {
      const token = await AsyncStorage.getItem('user_token');
      if (!token) {
        Alert.alert("Error", "Session expired. Please login again.");
        return;
      }

      const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=list_vet_appointments&api_token=${token}`);
      const data = await res.json();
      
      if (data.success) {
        setAppointments(data.appointments || []);
      } else {
        console.warn("API Error:", data.error);
      }
    } catch (err) {
      console.error("Fetch error:", err);
      Alert.alert("Error", "Could not connect to the server.");
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => { fetchData(); }, []);

  const fetchPetDetails = async (petId) => {
    setInfoLoading(true);
    setPetModalVisible(true);
    try {
      const token = await AsyncStorage.getItem('user_token');
      const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=get_pet_details&pet_id=${petId}&api_token=${token}`);
      const data = await res.json();
      if (data.success) {
        setPetInfo(data);
      }
    } catch (err) {
      Alert.alert("Error", "Failed to load.");
    } finally {
      setInfoLoading(false);
    }
  };

  const handleAddRecord = async () => {
    if (!treatment.title || treatment.description.length < 10) {
      Alert.alert("Error", "Title required, description min 10 chars.");
      return;
    }
    try {
      const token = await AsyncStorage.getItem('user_token');
      const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=add_record&api_token=${token}`, {
        method: 'POST',
        headers: { 
          'Content-Type': 'application/json',
          'Accept': 'application/json'
        },
        body: JSON.stringify({ 
          pet_id: selectedPet.pet_id,
          title: treatment.title,
          description: treatment.description,
          next_control_date: treatment.next_control_date 
        })
      });
      const data = await res.json();
      if (data.success) {
        setModalVisible(false);
        setTreatment({ title: '', description: '', next_control_date: '' });
        Alert.alert("Success", "Record saved.");
        fetchData(); 
      } else {
        Alert.alert("Error", data.error || "Failed to save record.");
      }
    } catch (err) { 
      console.error(err);
      Alert.alert("Error", "Network error.");
    }
  };

  const handleCancel = async () => {
    if (!cancelReason) return;
    try {
      const token = await AsyncStorage.getItem('user_token');
      const res = await fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_appointment.php?action=cancel_by_vet&api_token=${token}`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ appointment_id: selectedPet.id, reason: cancelReason })
      });
      const data = await res.json();
      if (data.success) {
        setCancelModalVisible(false);
        setCancelReason('');
        fetchData();
      }
    } catch (err) { console.error(err); }
  };

  const renderItem = ({ item }) => (
    <View style={styles.card}>
      <View style={styles.cardHeader}>
        <Text style={styles.petName}>{item.pet_name}</Text>
        <View style={[styles.badge, { backgroundColor: item.cancel_message ? '#FFCDD2' : '#C8E6C9' }]}>
          <Text style={{ color: item.cancel_message ? '#C62828' : '#2E7D32', fontSize: 12, fontWeight: 'bold' }}>
            {item.cancel_message ? 'Cancelled' : 'Active'}
          </Text>
        </View>
      </View>
      <Text style={styles.infoText}>👤 Owner: {item.first_name} {item.last_name}</Text>
      <Text style={styles.infoText}>📅 Time: {item.appointment_time}</Text>
      <View style={styles.actionRow}>
        <TouchableOpacity style={[styles.btn, styles.btnGreen]} onPress={() => fetchPetDetails(item.pet_id)}>
          <Text style={styles.btnText}>Pet Info</Text>
        </TouchableOpacity>
        <TouchableOpacity style={[styles.btn, styles.btnGreen]} onPress={() => { setSelectedPet(item); setModalVisible(true); }}>
          <Text style={styles.btnText}>Treat</Text>
        </TouchableOpacity>
        {!item.cancel_message && (
          <TouchableOpacity style={[styles.btn, styles.btnRed]} onPress={() => { setSelectedPet(item); setCancelModalVisible(true); }}>
            <Text style={styles.btnText}>Cancel</Text>
          </TouchableOpacity>
        )}
      </View>
    </View>
  );

  if (loading) return (
    <View style={styles.center}>
      <ActivityIndicator size="large" color="#4CAF50" />
    </View>
  );

  return (
    <View style={styles.container}>
      <Text style={styles.mainTitle}>Appointments</Text>
      
      <FlatList 
        data={appointments} 
        keyExtractor={(item) => item.id.toString()} 
        renderItem={renderItem}
        ListEmptyComponent={
          <View style={styles.emptyContainer}>
            <Text style={styles.emptyText}>No upcoming appointments found.</Text>
            <TouchableOpacity onPress={fetchData} style={styles.refreshBtn}>
              <Text style={styles.refreshBtnText}>Refresh</Text>
            </TouchableOpacity>
          </View>
        }
      />

      <Modal visible={modalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Add Treatment: {selectedPet?.pet_name}</Text>
            <TextInput style={styles.input} placeholder="Treatment Title" placeholderTextColor="#999" value={treatment.title} onChangeText={(t) => setTreatment({...treatment, title: t})} />
            <TextInput style={[styles.input, { height: 80 }]} multiline placeholder="Detailed Description..." placeholderTextColor="#999" value={treatment.description} onChangeText={(t) => setTreatment({...treatment, description: t})} />
            <TextInput style={styles.input} placeholder="Next Control Date (YYYY-MM-DD)" placeholderTextColor="#999" value={treatment.next_control_date} onChangeText={(t) => setTreatment({...treatment, next_control_date: t})} />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={styles.btnSave} onPress={handleAddRecord}><Text style={styles.btnText}>Save</Text></TouchableOpacity>
              <TouchableOpacity style={styles.btnCancel} onPress={() => setModalVisible(false)}><Text style={styles.btnText}>Close</Text></TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={cancelModalVisible} animationType="fade" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={styles.modalContent}>
            <Text style={styles.modalTitle}>Cancel Appointment</Text>
            <TextInput style={styles.input} placeholder="Reason for cancellation..." placeholderTextColor="#999" onChangeText={setCancelReason} />
            <View style={styles.modalButtons}>
              <TouchableOpacity style={[styles.btn, styles.btnRed]} onPress={handleCancel}><Text style={styles.btnText}>Confirm Cancel</Text></TouchableOpacity>
              <TouchableOpacity style={styles.btnCancel} onPress={() => setCancelModalVisible(false)}><Text style={styles.btnText}>Back</Text></TouchableOpacity>
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={petModalVisible} animationType="slide" transparent={true}>
        <View style={styles.modalOverlay}>
          <View style={[styles.modalContent, { maxHeight: '85%' }]}>
            <Text style={styles.modalTitle}>Pet Records</Text>
            {infoLoading ? (
              <ActivityIndicator color="#4CAF50" />
            ) : petInfo ? (
              <ScrollView>
                <View style={styles.infoSection}>
                   <Text style={{fontSize: 18, fontWeight: 'bold', color: '#4CAF50'}}>{petInfo.pet.name}</Text>
                   <Text style={{color: '#666'}}>{petInfo.pet.species} - {petInfo.pet.breed} ({petInfo.pet.age} years)</Text>
                </View>
                <Text style={styles.sectionTitle}>Medical History:</Text>
                {petInfo.treatments && petInfo.treatments.length > 0 ? (
                  petInfo.treatments.map((t, i) => (
                    <View key={i} style={styles.historyCard}>
                      <Text style={styles.historyDate}>{t.created_at}</Text>
                      <Text style={styles.historyTitle}>{t.title}</Text>
                      <Text style={styles.historyDesc}>{t.description}</Text>
                    </View>
                  ))
                ) : (
                  <Text style={{fontStyle: 'italic', color: '#999'}}>No records found.</Text>
                )}
              </ScrollView>
            ) : <Text>Error loading pet info.</Text>}
            <TouchableOpacity style={styles.btnInfoClose} onPress={() => {setPetModalVisible(false); setPetInfo(null);}}>
              <Text style={styles.btnText}>Got it</Text>
            </TouchableOpacity>
          </View>
        </View>
      </Modal>
    </View>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FFF8', padding: 15, paddingTop: 40 },
  center: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  mainTitle: { fontSize: 26, fontWeight: 'bold', marginBottom: 20, color: '#333' },
  card: { backgroundColor: '#fff', padding: 15, borderRadius: 12, marginBottom: 15, elevation: 3 },
  cardHeader: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', marginBottom: 10 },
  petName: { fontSize: 18, fontWeight: 'bold', color: '#4CAF50' },
  badge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 6 },
  infoText: { fontSize: 14, color: '#555', marginBottom: 4 },
  actionRow: { flexDirection: 'row', gap: 10, marginTop: 15 },
  btn: { flex: 1, paddingVertical: 10, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  btnGreen: { backgroundColor: '#4CAF50' },
  btnRed: { backgroundColor: '#FF5252' },
  btnText: { color: '#fff', fontWeight: 'bold', fontSize: 13 },
  modalOverlay: { flex: 1, backgroundColor: 'rgba(0,0,0,0.6)', justifyContent: 'center', padding: 20 },
  modalContent: { backgroundColor: '#fff', borderRadius: 15, padding: 20, elevation: 10 },
  modalTitle: { fontSize: 20, fontWeight: 'bold', marginBottom: 15 },
  input: { borderWidth: 1, borderColor: '#ddd', borderRadius: 8, padding: 12, marginBottom: 15, color: '#333' },
  modalButtons: { flexDirection: 'row', gap: 10 },
  btnSave: { flex: 2, backgroundColor: '#4CAF50', padding: 12, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  btnCancel: { flex: 1, backgroundColor: '#999', padding: 12, borderRadius: 8, alignItems: 'center', justifyContent: 'center' },
  btnInfoClose: { backgroundColor: '#4CAF50', padding: 12, borderRadius: 8, alignItems: 'center', marginTop: 15 },
  infoSection: { borderBottomWidth: 1, borderBottomColor: '#eee', paddingBottom: 10, marginBottom: 10 },
  sectionTitle: { fontWeight: 'bold', color: '#4CAF50', marginTop: 10, marginBottom: 10 },
  historyCard: { backgroundColor: '#f9f9f9', padding: 10, borderRadius: 8, marginBottom: 8, borderLeftWidth: 3, borderLeftColor: '#4CAF50' },
  historyDate: { fontSize: 11, color: '#999' },
  historyTitle: { fontWeight: 'bold', fontSize: 14, color: '#333' },
  historyDesc: { fontSize: 13, color: '#555', marginTop: 2 },
  emptyContainer: { alignItems: 'center', marginTop: 50 },
  emptyText: { color: '#999', fontSize: 16 },
  refreshBtn: { marginTop: 15, padding: 10, backgroundColor: '#4CAF50', borderRadius: 8 },
  refreshBtnText: { color: '#fff', fontWeight: 'bold' }
});