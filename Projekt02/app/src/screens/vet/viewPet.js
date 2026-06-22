import React, { useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, ActivityIndicator, Image } from 'react-native';

export default function ViewPet({ route }) {
  const { petId } = route.params;
  const [petData, setPetData] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetch(`https://nak.stud.vts.su.ac.rs/public/api/api_vet.php?action=get_pet_details&pet_id=${petId}`)
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          setPetData(data);
        }
        setLoading(false);
      })
      .catch(err => {
        console.error(err);
        setLoading(false);
      });
  }, [petId]);

  if (loading) {
    return (
      <View style={[styles.container, { justifyContent: 'center' }]}>
        <ActivityIndicator size="large" color="#4CAF50" />
      </View>
    );
  }

  if (!petData || !petData.pet) {
    return (
      <View style={styles.container}>
        <Text style={styles.errorText}>Pet not found.</Text>
      </View>
    );
  }

  return (
    <ScrollView style={styles.container}>
      <View style={styles.headerCard}>
        <Image 
          source={{ 
            uri: petData.pet.photo 
              ? `https://nak.stud.vts.su.ac.rs/public/${petData.pet.photo}` 
              : 'https://via.placeholder.com/150' 
          }} 
          style={styles.image} 
        />
        <Text style={styles.name}>{petData.pet.name}</Text>
        <Text style={styles.breed}>{petData.pet.species} | {petData.pet.breed}</Text>
        <Text style={styles.age}>Age: {petData.pet.age} years</Text>
      </View>

      <View style={styles.section}>
        <Text style={styles.sectionTitle}>Medical History</Text>
        {petData.treatments && petData.treatments.length > 0 ? (
          petData.treatments.map((t, index) => (
            <View key={index} style={styles.historyItem}>
              <Text style={styles.date}>{t.created_at}</Text>
              <Text style={styles.treatmentTitle}>{t.title}</Text>
              <Text style={styles.desc}>{t.description}</Text>
            </View>
          ))
        ) : (
          <Text style={styles.noData}>No medical records found.</Text>
        )}
      </View>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: { flex: 1, backgroundColor: '#F8FFF8' },
  headerCard: { alignItems: 'center', padding: 20, backgroundColor: '#fff', borderBottomWidth: 1, borderColor: '#eee', paddingTop: 40 },
  image: { width: 120, height: 120, borderRadius: 60, marginBottom: 10, backgroundColor: '#eee' },
  name: { fontSize: 24, fontWeight: 'bold', color: '#333' },
  breed: { fontSize: 16, color: '#666' },
  age: { fontSize: 14, color: '#888', marginTop: 5 },
  section: { padding: 20 },
  sectionTitle: { fontSize: 18, fontWeight: 'bold', marginBottom: 15, color: '#4CAF50' },
  historyItem: { backgroundColor: '#fff', padding: 15, borderRadius: 10, marginBottom: 12, elevation: 3, shadowColor: '#000', shadowOpacity: 0.1, shadowRadius: 5 },
  date: { fontSize: 12, color: '#999', marginBottom: 2 },
  treatmentTitle: { fontSize: 16, fontWeight: 'bold', color: '#333' },
  desc: { fontSize: 14, color: '#555', marginTop: 5, lineHeight: 20 },
  errorText: { textAlign: 'center', marginTop: 50, fontSize: 16, color: '#666' },
  noData: { textAlign: 'center', color: '#999', marginTop: 20 }
});